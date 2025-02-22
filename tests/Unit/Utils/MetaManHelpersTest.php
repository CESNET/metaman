<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MetaManHelpersTest extends TestCase
{
    #[Test]
    public function generateFederationId_replaces_czech_characters_with_ascii()
    {
        $this->assertEquals('escrzyaieuudtn', generateFederationID('ěščřžýáíéúůďťň'));
    }

    #[Test]
    public function generateFederationId_replaces_white_spaces_with_a_dash()
    {
        $this->assertEquals('eduid-cz', generateFederationID('eduid cz'));
        $this->assertEquals('eduid-cz', generateFederationID('eduid  cz'));
        $this->assertEquals('eduid-cz', generateFederationID('eduid   cz'));
        $this->assertEquals('edu-id-cz', generateFederationID('edu id   cz'));
    }

    #[Test]
    public function generateFederationId_replaces_two_or_more_dashes_with_just_one()
    {
        $this->assertEquals('eduid-cz', generateFederationID('eduid--cz'));
        $this->assertEquals('eduid-cz', generateFederationID('eduid---cz'));
        $this->assertEquals('eduid-cz', generateFederationID('eduid----cz'));
    }

    #[Test]
    public function generateFederationId_replaces_two_or_more_underscores_whit_just_one()
    {
        $this->assertEquals('eduid_cz', generateFederationID('eduid__cz'));
        $this->assertEquals('eduid_cz', generateFederationID('eduid___cz'));
        $this->assertEquals('eduid_cz', generateFederationID('eduid____cz'));
    }

    #[Test]
    public function generateFederationId_produces_only_lowercased_characters()
    {
        $this->assertEquals('eduidcz', generateFederationID('eduIDcz'));
    }

    #[Test]
    public function generateFederationId_produces_only_valid_output_for_quite_an_ugly_input()
    {
        $this->assertEquals('eduidcz', generateFederationID('eduID..cz'));
        $this->assertEquals('eduidcz', generateFederationID('e.d.u.I.D..cz'));
        $this->assertEquals('ces-net-int', generateFederationID('CES-NET----int'));
        $this->assertEquals('', generateFederationID(''));
        $this->assertEquals('_-cz-in_ternal', generateFederationID('_ ČŽ  ---- in___terná.....l'));
    }
}
