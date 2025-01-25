document.addEventListener("DOMContentLoaded", function () {
    const $ = jQuery;
    const login_button = '<span id="cpmwp_metamask_login"></span>';
    const form_data = $("#loginform");
    const last_element = $(form_data).find('p:last-child');
    const wc_selector = $('.woocommerce-form.woocommerce-form-login.login').find('.woocommerce-form-login__submit');
    const enable_metamask_button = login_data_db.enable_metamask_button;

    if (enable_metamask_button.enable_at_wc_form === "1") {
        $(wc_selector).parent().append(login_button);
    }
    if (enable_metamask_button.enable_at_wp_admin === "1") {
        $(last_element).append(login_button);
    }
    if (enable_metamask_button.enable_at_wc_chk_form === "1") {
        $('.checkout.woocommerce-checkout').prepend(login_button);
    }
});
