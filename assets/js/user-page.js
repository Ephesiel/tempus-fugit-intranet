
function tfi_set_disable_button(max, min, class_element, class_to_disable_on_max, class_to_disable_on_min) {
    if (min > max) {
        return;
    }

    // -1 Because there is the hidden field which is not count.
    let length = document.getElementsByClassName(class_element).length - 1;
    let remove_buttons = document.getElementsByClassName(class_to_disable_on_min);
    let add_buttons = document.getElementsByClassName(class_to_disable_on_max);

    console.log(min, max, length);

    if (length <= min) {
        console.log('disabled remove');
        [].forEach.call(remove_buttons, function(element) {
            element.setAttribute("disabled", true);
        });
    }
    else {
        console.log('enable remove');
        [].forEach.call(remove_buttons, function(element) {
            element.removeAttribute("disabled");
        });
    }

    if ( length >= max ) {
        console.log('disabled add');
        [].forEach.call(add_buttons, function(element) {
            element.setAttribute("disabled", true);
        });
    }
    else {
        console.log('enable add');
        [].forEach.call(add_buttons, function(element) {
            element.removeAttribute("disabled");
        });
    }
}