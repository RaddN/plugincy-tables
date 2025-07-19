jQuery(document).ready(function($) {
    let currentCell = null;
    let tableData = {
        headers: ["Column 1", "Column 2", "Column 3", "Column 4", "Column 5"],
        rows: [[
            {elements: []},
            {elements: []},
            {elements: []},
            {elements: []},
            {elements: []}
        ]],
        footers: ["Footer 1", "Footer 2", "Footer 3", "Footer 4", "Footer 5"],
        show_header: true,
        show_footer: false
    };
    
    window.plugincyLoadTableData = function(data) {
        tableData = data;
        renderTable();
    };
    
    function renderTable() {
        const $table = $("#plugincy-editable-table");
        const $header = $table.find("thead tr");
        const $body = $table.find("tbody");
        const $footer = $table.find("tfoot tr");
        
        $header.empty();
        tableData.headers.forEach(function(header, index) {
            $header.append(`<th contenteditable="true" class="plugincy-editable-header" data-index="${index}">${header}</th>`);
        });
        $header.append(`<th class="plugincy-action-column">Actions</th>`);
        
        $body.empty();
        tableData.rows.forEach(function(row, rowIndex) {
            let $row = $("<tr>");
            row.forEach(function(cell, cellIndex) {
                let cellContent = "";
                if (cell.elements && cell.elements.length > 0) {
                    cell.elements.forEach(function(element) {
                        cellContent += `<div class="plugincy-element" data-type="${element.type}">${element.type.replace(/_/g, " ")}</div>`;
                    });
                } else {
                    cellContent = `<div class="plugincy-add-element">+</div>`;
                }
                $row.append(`<td class="plugincy-editable-cell" data-row="${rowIndex}" data-col="${cellIndex}"><div class="plugincy-cell-content">${cellContent}</div></td>`);
            });
            $row.append(`<td class="plugincy-action-column"><button type="button" class="button button-small delete-row">Delete</button></td>`);
            $body.append($row);
        });
        
        $footer.empty();
        tableData.footers.forEach(function(footer, index) {
            $footer.append(`<td contenteditable="true" class="plugincy-editable-footer" data-index="${index}">${footer}</td>`);
        });
        $footer.append(`<td></td>`);
        
        $("#show-header").prop("checked", tableData.show_header);
        $("#show-footer").prop("checked", tableData.show_footer);
        
        if (tableData.show_header) {
            $table.find("thead").show();
        } else {
            $table.find("thead").hide();
        }
        
        if (tableData.show_footer) {
            $table.find("tfoot").show();
        } else {
            $table.find("tfoot").hide();
        }
    }
    
    // Function to serialize table data
    function serializeTableData() {
        return JSON.stringify(tableData);
    }
    
    // Handle form submission
    $(document).on("submit", "#plugincy-table-form", function(e) {
        // Serialize the table data before submitting
        $("#table-data-input").val(serializeTableData());
    });
    
    $(document).on("click", ".plugincy-add-element", function() {
        currentCell = $(this).closest(".plugincy-editable-cell");
        $("#plugincy-element-modal").show();
    });
    
    $(document).on("click", ".plugincy-element-option", function() {
        const elementType = $(this).data("type");
        const rowIndex = currentCell.data("row");
        const colIndex = currentCell.data("col");
        
        if (!tableData.rows[rowIndex][colIndex].elements) {
            tableData.rows[rowIndex][colIndex].elements = [];
        }
        
        let elementContent = "";
        if (elementType === "custom_text") {
            elementContent = prompt("Enter custom text:");
            if (elementContent === null) {
                return; // User cancelled
            }
        }
        
        tableData.rows[rowIndex][colIndex].elements.push({
            type: elementType,
            content: elementContent
        });
        
        renderTable();
        $("#plugincy-element-modal").hide();
    });
    
    $(document).on("click", ".plugincy-close", function() {
        $("#plugincy-element-modal").hide();
    });
    
    $(document).on("click", "#add-column", function() {
        const newIndex = tableData.headers.length;
        tableData.headers.push("Column " + (newIndex + 1));
        tableData.footers.push("Footer " + (newIndex + 1));
        
        tableData.rows.forEach(function(row) {
            row.push({elements: []});
        });
        
        renderTable();
    });
    
    $(document).on("click", "#add-row", function() {
        const newRow = [];
        for (let i = 0; i < tableData.headers.length; i++) {
            newRow.push({elements: []});
        }
        tableData.rows.push(newRow);
        renderTable();
    });
    
    $(document).on("click", ".delete-row", function() {
        const rowIndex = $(this).closest("tr").index();
        if (tableData.rows.length > 1) {
            tableData.rows.splice(rowIndex, 1);
            renderTable();
        } else {
            alert("Cannot delete the last row!");
        }
    });
    
    $(document).on("blur", ".plugincy-editable-header", function() {
        const index = $(this).data("index");
        tableData.headers[index] = $(this).text();
    });
    
    $(document).on("blur", ".plugincy-editable-footer", function() {
        const index = $(this).data("index");
        tableData.footers[index] = $(this).text();
    });
    
    $(document).on("change", "#show-header", function() {
        tableData.show_header = $(this).is(":checked");
        renderTable();
    });
    
    $(document).on("change", "#show-footer", function() {
        tableData.show_footer = $(this).is(":checked");
        renderTable();
    });
    
    $(document).on("click", ".plugincy-element", function() {
        if (confirm("Remove this element?")) {
            const $cell = $(this).closest(".plugincy-editable-cell");
            const rowIndex = $cell.data("row");
            const colIndex = $cell.data("col");
            const elementType = $(this).data("type");
            
            tableData.rows[rowIndex][colIndex].elements = tableData.rows[rowIndex][colIndex].elements.filter(function(element) {
                return element.type !== elementType;
            });
            
            renderTable();
        }
    });
    
    $(document).on("click", ".copy-shortcode", function() {
        const shortcode = $(this).data("shortcode");
        navigator.clipboard.writeText(shortcode).then(function() {
            alert("Shortcode copied to clipboard!");
        }).catch(function() {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = shortcode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert("Shortcode copied to clipboard!");
        });
    });
    
    $(document).on("click", ".delete-table", function() {
        if (confirm("Are you sure you want to delete this table?")) {
            const tableId = $(this).data("id");
            const $row = $(this).closest("tr");
            
            $.ajax({
                url: plugincy_ajax.ajax_url,
                type: "POST",
                data: {
                    action: "delete_table",
                    nonce: plugincy_ajax.nonce,
                    id: tableId
                },
                success: function(response) {
                    if (response.success) {
                        $row.remove();
                        alert("Table deleted successfully!");
                    } else {
                        alert("Error: " + response.data);
                    }
                },
                error: function() {
                    alert("An error occurred while deleting the table.");
                }
            });
        }
    });
    
    $(window).on("click", function(event) {
        if (event.target.id === "plugincy-element-modal") {
            $("#plugincy-element-modal").hide();
        }
    });
    
    // Initialize the table on page load
    renderTable();
});