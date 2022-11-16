import "./bootstrap";

import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

// $(".open-modal").on("click", function (event) {
//     event.preventDefault();
//     var target = $(this).attr("data-target");
//     $("#" + target + "-modal").toggleClass("hidden");
// });

// $(".close-modal").on("click", function () {
//     $(".modal-overlay").each(function () {
//         $(this).addClass("hidden");
//     });
// });
