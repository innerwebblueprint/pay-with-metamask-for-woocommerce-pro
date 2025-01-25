const $ = jQuery;
const url = window.location.href;
const settingsPage = '?page=cpmw-metamask-settings';
const settingsSelector = 'a[href=\"admin.php?page=cpmw-metamask-settings\"]';

if (url.includes(settingsPage)) {
    $(settingsSelector).parent('li').addClass('current');
}

$('#adminmenu #toplevel_page_woocommerce ul li').find(settingsSelector).each(function () {
    if ($(this).is(':empty')) {
        $(this).hide();
    }
});