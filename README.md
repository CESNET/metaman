# MetaMan-Laravel

MetaMan (short for Metadata Manager) is [Laravel](https://laravel.com)-based web application intended for managing and storing SAML metadata for identity federations within [Git](https://git-scm.com) version control system.

[![Actions Status](https://github.com/JanOppolzer/metaman-laravel/workflows/Laravel/badge.svg)](https://github.com/JanOppolzer/metaman-laravel/actions)

## Requirements

This application is written in Laravel 10 and uses PHP Enumerations thus it requires PHP version 8.1.0 or newer.

Authentication is expected to be managed by locally running Shibboleth Service Provider, so Apache web server is highly recommended as there is an official Shibboleth module for it. There is also an unofficial Shibboleth SP module for nginx web server, however, it has not been tested and so it is not recommended.

- PHP 8.1.0+
- Shibboleth SP 3.x
- Apache 2.4
- Supervisor 4.1

The above mentioned requirements can be easily achieved by using Ubuntu 22.04 LTS (Jammy Jellyfish). For those running older Ubuntu or Debian, [Ondřej Surý's PPA repository](https://launchpad.net/~ondrej/+archive/ubuntu/php/) might be very appreciated.

### Setup

In order for the Envoy script to be really useful and do what it is designed for, you must setup Apache, PHP, Shibboleth SP and Supervisor at the destination host first.

(To prepare a server for MetaMan, I am using an [Ansible](https://www.ansible.com) playbook that is currently not publicly available due to being part of our larger and internal mechanism, but I am willing to share it and most probably will do that in the future.)

#### Apache

Install Apache using `apt`.

```bash
apt install apache2
```

Then get a TLS certificate. If you would like to avoid paying to a Certificate Authority, use [Certbot](https://certbot.eff.org) to get a certificate from [Let's Encrypt](https://letsencrypt.org) for free. Then configure Apache securely according to [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/#server=apache) using the following stub.

```apache
<VirtualHost *:80>
    ServerName      server.example.org
    Redirect        permanent / https://server.example.org
</VirtualHost>

<VirtualHost _default_:443>
    ServerName      server.example.org
    DocumentRoot    /home/web/metaman/current/public/

    # TLS settings

    <Directory /home/web/metaman/current/public>
        AllowOverride All
    </Directory>

    <Location />
        AuthType shibboleth
        ShibRequestSetting requireSession 0
        <RequireAll>
            Require shibboleth
        </RequireAll>
    </Location>

    <Location /Shibboleth.sso>
        SetHandler shib
    </Location>
</VirtualHost>
```

It is also highly recommended to allow `web` user (the user defined in `config` file in the `TARGET_USER` variable, i.e. the one under which MetaMan application is saved in `/home` directory) to reload and restart PHP-FPM. It helps with minimizing outage during deployment of a new version. Edit `/etc/sudoers.d/web` accordingly:

```text
web ALL=(ALL) NOPASSWD:/bin/systemctl reload php8.1-fpm,/bin/systemctl restart php8.1-fpm
```

#### PHP

PHP 8.1 is present as an official package in recommended Ubuntu 22.04 LTS (Jammy Jellyfish).

```bash
apt install php-fpm
```

Then follow information in your terminal.

```bash
a2enmod proxy_fcgi setenvif
a2enconf php8.1-fpm
systemctl restart apache2
```

(In case you still run on older Ubuntu version or Debian distribution with not so current PHP version, you might find [Ondřej Surý's repository](https://launchpad.net/~ondrej/+archive/ubuntu/php) highly useful.)

#### Shibboleth SP

Install and configure Shibboleth SP.

```bash
apt install libapache2-mod-shib
```

There is a [documentation](https://www.eduid.cz/cs/tech/sp/shibboleth) (in Czech language, though) available at [eduID.cz](https://www.eduid.cz/cs/tech/sp/shibboleth) federation web page.

You should add _AttributeChecker_ (MetaMan requires _uniqueId_, _mail_ and _cn_ attributes) and _AttributeExtractor_ (to obtain useful information from federation metadata).

```xml
<ApplicationDefaults entityID="https://server.example.org/shibboleth"
    REMOTE_USER="uniqueId"
    sessionHook="/Shibboleth.sso/AttrChecker"
    metadataAttributePrefix="Meta-"
    cipherSuites="DEFAULT:!EXP:!LOW:!aNULL:!eNULL:!DES:!IDEA:!SEED:!RC4:!3DES:!kRSA:!SSLv2:!SSLv3:!TLSv1:!TLSv1.1">

    <Sessions lifetime="28800" timeout="3600" relayState="ss:mem"
        checkAddress="false" handlerSSL="true" cookieProps="https"
        redirectLimit="exact">

        <!-- Attribute Checker -->
        <Handler type="AttributeChecker" Location="/AttrChecker" template="attrChecker.html"
            attributes="uniqueId mail cn" flushSession="true"/>

    </Sessions>

    <!-- Extract information from SAML metadata -->
    <AttributeExtractor type="Metadata" DisplayName="displayName"
        InformationURL="informationURL" OrganizationURL="organizationURL">
        <ContactPerson id="Technical-Contact" contactType="technical" formatter="$EmailAddress"/>
    </AttributeExtractor>

</ApplicationDefaults>
```

Then tweak `attrChecker.html`, `localLogout.html` and `metadataError.html` so users are informed properly when any issue occurs. If you need any help, check GÉANT's documentation regarding [attribute checking](https://wiki.geant.org/display/eduGAIN/How+to+configure+Shibboleth+SP+attribute+checker).

#### Supervisor

Supervisor is a process monitor for the Linux operating system and helps you with queuing jobs to background processes. The setup is fairly easy and [documented](https://laravel.com/docs/9.x/queues#supervisor-configuration) by Laravel.

Install supervisor.

```bash
apt install supervisor
```

Prepare configuration file for your worker.

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/web/metaman/current/artisan queue:work
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home/web/laravel-worker.log
stopwaitsecs=3600
```

Reread configuration and start the process.

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
```

## Installation

The easiest way to install MetaMan is to use Laravel [Envoy](https://laravel.com/docs/9.x/envoy) script that is included in this repository.

Laravel Envoy is currently available only for _macOS_ and _Linux_ operating systems. However, on Windows you can use [Windows Subsystem for Linux](https://docs.microsoft.com/en-us/windows/wsl/install-win10). Of course, you can also use a virtualized Linux system inside, for example, a [VirtualBox](https://www.virtualbox.org) machine.

The destination host should be running Ubuntu 22.04 LTS (Jammy Jellyfish) with PHP 8.1. If that is not the case, take care and tweak PHP-FPM service in `Envoy.blade.php` and in Apache configuration accordingly.

Clone this repository:

```bash
git clone https://github.com/JanOppolzer/metaman
```

Install PHP dependencies:

```bash
composer install
```

Prepare a _configuration file_ for your deployment using `envoy.example` template.

```bash
cp envoy.example envoy
```

Tweaking `envoy` file to your needs should be easy as all the variables within the file are self explanatory. Then just run the _deploy_ task.

```bash
./vendor/bin/envoy run deploy
```

### Tasks

There are three different tasks available — `deploy`, `rollback` and `cleanup`.

#### deploy

The `deploy` task simply deploys the current MetaMan version available at GitHub into timestamped directory and makes a symbolic link `current`. This helps you with rolling back to the previous version.

```bash
./vendor/bin/envoy run deploy
```

#### rollback

The `rollback` task is there to help you roll back to the previous version if there is an issue with the current one. It just finds the previous timestamped directory and changes `current` symbolic link to that directory.

```bash
./vendor/bin/envoy run rollback
```

In case you would like to go back even further, just `ssh` into your web server and create a symbolic link to any version you have still available there.

#### cleanup

The `cleanup` task helps you keeping your destination directory clean by leaving only three latest versions (i.e. timestamped directories) available and deletes all the older versions.

```bash
./vendor/bin/envoy run cleanup
```

### Why no stories?

_Laravel Envoy_ allows to use "stories" to help with grouping a set of tasks. Eventually, it makes the whole script much more readable as well as reusable.

There is one downside with stories, though. If your SSH agent requests confirming every use of your key (a highly recommended best practice!), you must confirm the key usage for every single Envoy story. I find it **very** annoying so therefore I have decided not to use stories after all.
