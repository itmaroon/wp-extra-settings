jQuery(document).ready(function ($) {
  $(".itmar-settings-tabs__nav-button").click(function () {
    var tab_id = $(this).data("tab");

    $(".itmar-settings-tabs__nav-button").removeClass("active");
    $(".itmar-settings-content__section").removeClass("active");

    $(this).addClass("active");
    $("#" + tab_id).addClass("active");

    // すでにアクティブならフォーカスを当てて、アニメーションを発動
    if ($(this).hasClass("active")) {
      $(this).focus();
    }
  });
});
