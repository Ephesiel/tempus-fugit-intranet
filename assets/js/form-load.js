
/**
 * tfi_display_form
 * 
 * Display the connection form to the intranet
 * The form can be hide by passing display = false
 * 
 * @since 1.0.0
 * 
 * @param {boolean} [display=true] Should we display or hide the form
 */
function tfi_display_form(display = true) {
    document.getElementById("tfi-form-container").style.display = display ? "" : "none";
    tfi_is_form_display = display;
}

/**
 * tfi_redirect_user_page
 * 
 * Allow to automatically redirect to the user page when this one is set (it is whenthe current user can access to intranet)
 */
function tfi_redirect_user_page() {
    if ( tfi_user_page_url != undefined ) {
        window.location.href = tfi_user_page_url;
    }
}

/**
 * Verify if the combo key is the shortcut to display the connection form
 * 
 * @since 1.0.0
 */
document.addEventListener('keydown', function(event) {
    var evtobj = window.event ? window.event : event;

    /**
     * When press the Escape button
     */
    if (tfi_is_form_display && evtobj.keyCode == 27) {
        tfi_display_form(false);
        return;
    }
    if (typeof tfi_form_shortcut !== "undefined") {
        if (tfi_form_shortcut.ctrl_key_used !== "undefined" && tfi_form_shortcut.ctrl_key_used && !evtobj.ctrlKey)
            return;
        if (tfi_form_shortcut.alt_key_used !== "undefined" && tfi_form_shortcut.alt_key_used && !evtobj.altKey)
            return;
        if (tfi_form_shortcut.shift_key_used !== "undefined" && tfi_form_shortcut.shift_key_used && !evtobj.shiftKey)
            return;

        if (tfi_form_shortcut.key !== "undefined" && evtobj.keyCode == tfi_form_shortcut.key)
            tfi_display_form(!tfi_is_form_display);
        return;
    }
});

window.addEventListener("DOMContentLoaded", function(event) {

    if (document.getElementById("tfi-form-container").style.display != 'none') {
        tfi_is_form_display = true;
    }

    /**
     * Verify if the user click outside the form box, if it is, the form will disappear
     * 
     * @since 1.0.0
     */
    document.getElementById("tfi-form-container").addEventListener('click', function(event) {
        if (tfi_is_form_display) {
            let rect = document.getElementById("wrapper").getBoundingClientRect();
            if (rect.top > event.y || rect.bottom < event.y || rect.left > event.x || rect.right < event.x) {
                tfi_display_form(false);
            }
        }
    });
});

let tfi_is_form_display = false;