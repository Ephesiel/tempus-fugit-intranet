/**
 * Tfi_add_row.
 * 
 * Add a row to a given table
 * The table need to have a last row which is a template to the new row
 * Clones this template and change ids and names
 *  
 * @since 1.0.0
 * 
 * @param {string} [table_id]       The id of the table were the row will be added
 * @param {string} [row_ids_suffix] The string to put before the id (the id is a number)
 * @param {string} [replace_value]  The given value will be changed by the new id (without the suffix) on name and onclick attribute
 */
function tfi_add_row(table_id, row_ids_suffix, replace_value) {
    let table = document.querySelector("#" + table_id + " tbody");
    let row_to_clone = table.lastElementChild;
    let row_to_add = row_to_clone.cloneNode(true);
    let existing_row;
    let counter = 0;
    do {
        counter++;
        existing_row = document.getElementById(row_ids_suffix + counter);
    } while (existing_row != undefined);
    row_to_add.setAttribute("id", row_ids_suffix + counter);
    row_to_add.querySelectorAll("*").forEach(function(item) {
        if (item.getAttribute("name") != null) {
            item.setAttribute("name", item.getAttribute("name").replace(replace_value, counter));
        }
        if (item.getAttribute("onclick") != null) {
            console.log(item.getAttribute("onclick"), item.getAttribute("onclick").replace(replace_value, counter));
            item.setAttribute("onclick", item.getAttribute("onclick").replace(replace_value, counter));
        }
    });
    row_to_add.removeAttribute("hidden");
    table.insertBefore(row_to_add, row_to_clone);
}

/**
 * Tfi_remove_row.
 * 
 * Remove the row with the id
 * It can be any element but the initial goal is for table row
 *  
 * @since 1.0.0
 * 
 * @param {string} id_to_remove The row id 
 */
function tfi_remove_row(id_to_remove) {
    document.getElementById(id_to_remove).remove();
}

/**
 * Tfi_move_row_to_up.
 * 
 * Move a row to the up
 *  
 * @since 1.0.0
 * 
 * @param {string} id_to_move The row id
 */
function tfi_move_row_to_up(id_to_move) {
    let bottom_element          = document.getElementById(id_to_move);
    let top_element             = bottom_element.previousElementSibling;

    bottom_element.parentNode.insertBefore(bottom_element, top_element);
    tfi_hide_first_row_button();
}

/**
 * Tfi_hide_first_row_button.
 * 
 * Hide the first button which allow to permute rows.
 * It usefull because the first row cannot move up obviously
 *  
 * @since 1.0.0
 */
function tfi_hide_first_row_button() {
    if (tfi_first_field_row_button != undefined ) {
        tfi_first_field_row_button.removeAttribute("hidden");
    }
    tfi_first_field_row_button = document.querySelector("[id^=tfi-field-]:first-of-type .change-field-row");
    if (tfi_first_field_row_button != undefined) {
        tfi_first_field_row_button.setAttribute("hidden", true);
    }
}

window.addEventListener("DOMContentLoaded", function(event) {
    tfi_hide_first_row_button();
});

/**
 * Tfi_first_field_row_button.
 * 
 * Keep in mind the first button which is hide to avoid foreach statement on tfi_hide_first_row_button function
 * 
 * @since 1.0.0
 * 
 * @var {string}
 */
let tfi_first_field_row_button