/**
 * Univerzání funkce
 * 
 * @returns void
 */
$( function() {
    // datepicker - nastavení
    $.datepicker.regional['cs'] = {
        closeText: 'Zavřít',
        prevText: 'Dříve',
        nextText: 'Později',
        currentText: 'Dnes',
        monthNames: ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'],
        monthNamesShort: ['led', 'úno', 'bře', 'dub', 'kvě', 'čer', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'],
        dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
        dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
        dayNamesMin: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
        weekHeader: 'Týd',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: '',
        showButtonPanel: true
    };
    $.datepicker.setDefaults( $.datepicker.regional['cs'] );
    
    // datepicker Objednávky
    $( "#frm-orderDateForm-date" ).datepicker( $.datepicker.regional );
    
    // datepicker modal Objednávky
    $( "#frm-orderForm-date" ).datepicker( $.datepicker.regional );

    // datepicker Diář
    $( "#frm-diaryDateForm-date" ).datepicker( $.datepicker.regional );
 
    // datepicker modal Diář
    $( "#frm-diaryForm-date" ).datepicker( $.datepicker.regional );
});


/**
 * Vložení textu z editoru div-editor do inputu content
 * 
 * @returns void
 */
function copy_editor_to_content() {
    $('#frm-commentForm-content').val($('#div-editor').html());
}


/**
 * Vložení textu z inputu content do editoru div-editor
 * 
 * @returns void
 */
function copy_content_to_editor() {
    $('#div-editor').html($('#frm-commentForm-content').val());
}

