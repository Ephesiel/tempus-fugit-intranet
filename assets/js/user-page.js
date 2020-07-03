/**
 * Tfi_set_disable_button.
 * 
 * 
 * 
 * @since 1.2.2
 * 
 * @param {int}     max                     Maximum number of elements on the multiple field
 * @param {int}     min                     Minimum number of elements on the multiple field
 * @param {string}  class_element           Class which needs to have a max/min
 * @param {string}  class_to_disable_on_max Class for all add buttons (we can't add a new row if maximum element are already displayed)
 * @param {string}  class_to_disable_on_min Class for all remove buttons (we can't remove a row if just the minimum element are displayed)
 */
function tfi_set_disable_button(max, min, class_element, class_to_disable_on_max, class_to_disable_on_min) {
    if (min > max) {
        return;
    }

    // -1 Because there is the hidden field which is not count.
    let length = document.getElementsByClassName(class_element).length - 1;
    let remove_buttons = document.getElementsByClassName(class_to_disable_on_min);
    let add_buttons = document.getElementsByClassName(class_to_disable_on_max);

    if (length <= min) {
        [].forEach.call(remove_buttons, function(element) {
            element.setAttribute("disabled", true);
        });
    }
    else {
        [].forEach.call(remove_buttons, function(element) {
            element.removeAttribute("disabled");
        });
    }

    // When max = 0, it means infinite
    if (max != 0) {
        if (length >= max) {
            [].forEach.call(add_buttons, function(element) {
                element.setAttribute("disabled", true);
            });
        }
        else {
            [].forEach.call(add_buttons, function(element) {
                element.removeAttribute("disabled");
            });
        }
    }
}

window.addEventListener("DOMContentLoaded", function(event) {

    Array.from(document.getElementsByClassName("multiple-field-first-row")).forEach(function(element) {
        let max             = element.getAttribute('data-max');
        let min             = element.getAttribute('data-min');
        let element_class   = element.getAttribute('element-class');
        let add_class       = element.getAttribute('button-add-class');
        let remove_class    = element.getAttribute('button-remove-class');
        tfi_set_disable_button( max, min, element_class, add_class, remove_class );
    });
});