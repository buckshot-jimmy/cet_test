<?php

$root = __DIR__;
$jsDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js';
$cssPath = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'js-extracted.css';
$baseTemplate = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'base.html.twig';
$cssLink = '        <link href="{{ asset(\'assets/css/js-extracted.css\') }}" rel="stylesheet" type="text/css">';

$css = <<<'CSS'
/* Extracted from public/assets/js/*.js, excluding app_js. */

.js-mr-3,
.btn-circle.btn-sm {
    margin-right: 3px;
}

.servicii_documente {
    margin-left: 10px;
}

.tooltipHover,
.incaseaza {
    color: white;
}

.js-filter-blur {
    filter: blur(5px);
}

.js-z-index-1100 {
    z-index: 1100;
}

.js-font-bold {
    font-weight: bold;
}

.js-row-storno {
    background-color: #f3eaeb;
}

.js-overflow-x-auto {
    overflow-x: auto;
}

.js-tooltip-danger {
    background-color: #d52a1a !important;
}

.js-tooltip-success {
    background-color: #169b6b !important;
}

.js-text-black {
    color: black;
}

.js-text-success {
    color: #1cc88a;
}

.js-text-primary {
    color: #4e73df;
}

.js-service-cell {
    padding-right: 15px;
    padding-bottom: 15px;
}

.js-service-cell-empty {
    width: 150px;
    padding-right: 15px;
    padding-bottom: 15px;
}

.js-service-date-cell {
    width: 100px;
    padding-right: 15px;
    padding-bottom: 15px;
    font-weight: bold;
}

.js-unbilled-date-cell {
    width: 120px;
    padding-right: 15px;
    padding-bottom: 15px;
    font-weight: bold;
}

.js-service-medic-cell {
    width: 120px;
    font-weight: bold;
    padding-right: 15px;
    padding-bottom: 15px;
}

.js-service-name-cell {
    width: 150px;
    font-weight: bold;
    padding-right: 15px;
    padding-bottom: 15px;
}

.js-cell-bold-pad {
    font-weight: bold;
    padding-right: 15px;
    padding-bottom: 15px;
}

.js-cell-bold-bottom {
    font-weight: bold;
    padding-bottom: 15px;
}

.js-checkbox-cell {
    padding-left: 50px;
    vertical-align: top;
}

.js-row-pad-bottom {
    padding-bottom: 15px;
}

.js-service-button {
    width: 40px;
    height: 40px;
}

.js-service-button-hidden {
    width: 40px;
    height: 40px;
    display: none;
}

.js-service-button-hidden-narrow {
    width: 25px;
    height: 40px;
    display: none;
}

.js-service-button-edit {
    width: 40px;
    height: 40px;
    font-weight: bold;
}

.js-doc-button {
    width: 150px;
    height: 40px;
    font-size: 14px;
}

.js-empty-doc-button {
    width: 150px;
    height: 40px;
    display: none;
}

.js-checkbox-input {
    position: relative;
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    margin-right: 12px;
    padding-bottom: 15px;
}

.js-payment-header-cell,
.js-payment-total-cell {
    color: #78261f;
    font-weight: bold;
}

.js-service-row-middle {
    border-left: black solid 1px;
    border-right: black solid 1px;
    height: 40px;
}

.js-service-row-first {
    border-top: black solid 1px;
    border-left: black solid 1px;
    border-right: black solid 1px;
    height: 40px;
}

.js-service-row-last {
    border-bottom: black solid 1px;
    border-left: black solid 1px;
    border-right: black solid 1px;
    height: 40px;
}

.js-service-row-single {
    border: black solid 1px;
    height: 40px;
}
CSS;

file_put_contents($cssPath, $css . PHP_EOL);

$base = file_get_contents($baseTemplate);
if (!str_contains($base, 'assets/css/js-extracted.css')) {
    $needle = '        <link href="{{ asset(\'assets/css/twig-extracted.css\') }}" rel="stylesheet" type="text/css">';
    if (str_contains($base, $needle)) {
        $base = str_replace($needle, $needle . PHP_EOL . $cssLink, $base);
    } else {
        $fallback = '        <link href="{{ asset(\'assets/css/custom.css\') }}" rel="stylesheet" type="text/css">';
        $base = str_replace($fallback, $fallback . PHP_EOL . $cssLink, $base);
    }
    file_put_contents($baseTemplate, $base);
}

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsDir, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'js') {
        continue;
    }
    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'app_js' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $files[] = $file->getPathname();
}

$commonReplacements = [
    ' style="margin-right: 3px;"' => '',
    ' style="margin-left: 10px;"' => '',
    ' style="margin-right: 3px; color: white;"' => '',
    " style='margin-right: 3px; color: white;'" => '',
    'style="padding-right: 15px; padding-bottom: 15px;"' => 'class="js-service-cell"',
    'style="width:150px; padding-right: 15px; padding-bottom: 15px;"' => 'class="js-service-cell-empty"',
    'style="width: 100px; padding-right: 15px; padding-bottom: 15px; font-weight: bold;"' => 'class="js-service-date-cell"',
    'style="width: 120px; padding-right: 15px; padding-bottom: 15px; font-weight: bold;"' => 'class="js-unbilled-date-cell"',
    'style="width: 150px; font-weight: bold; padding-right: 15px; padding-bottom: 15px;"' => 'class="js-service-name-cell"',
    'style="width: 25px; height: 40px; display: none;"' => 'class="js-service-button-hidden-narrow"',
    'style="width: 40px; height: 40px; display: none;"' => 'class="js-service-button-hidden"',
    'style="width: 150px; height: 40px; display: none;"' => 'class="js-empty-doc-button"',
    'style="width:40px; padding-right: 15px; padding-bottom: 15px;"' => 'class="js-service-cell"',
    'style="padding-bottom: 15px;"' => 'class="js-row-pad-bottom"',
    'style="padding-left: 50px; vertical-align: top;"' => 'class="js-checkbox-cell"',
    'style="color: #78261f; font-weight: bold;"' => 'class="js-payment-header-cell"',
    "style='font-weight: bold; color: #78261f;'" => "class='js-payment-total-cell'",
    "style='color: #78261f; font-weight: bold;'" => "class='js-payment-total-cell'",
];

$fileReplacements = [
    'base.js' => [
        "function checkBoxStyle() {\n    return 'position: relative; width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 6px;' +\n        'margin-right: 12px; padding-bottom: 15px;';\n}" =>
            "function checkBoxStyle() {\n    return 'js-checkbox-input';\n}",
    ],
    'consultatii.js' => [
        '$(".servicii_documente_modal").css(\'filter\', \'blur()\');' => '$(".servicii_documente_modal").removeClass(\'js-filter-blur\');',
        '$(".servicii_documente_modal").css(\'filter\', \'blur(5px)\');' => '$(".servicii_documente_modal").addClass(\'js-filter-blur\');',
        '$(".servicii_documente_modal").css(\'filter\', \'blur(0)\');' => '$(".servicii_documente_modal").removeClass(\'js-filter-blur\');',
        '$(".modal_stergere").modal("show").css("z-index", 1100);' => '$(".modal_stergere").modal("show").addClass("js-z-index-1100");',
        '$(".titlu_servicii_documente_modal").css(\'font-weight\', \'bold\')' => '$(".titlu_servicii_documente_modal").addClass(\'js-font-bold\')',
        '$(".add_edit_consultatie_modal").css(\'filter\', \'blur(5px)\');' => '$(".add_edit_consultatie_modal").addClass(\'js-filter-blur\');',
        '$(".add_edit_investigatie_modal").css(\'filter\', \'blur(5px)\');' => '$(".add_edit_investigatie_modal").addClass(\'js-filter-blur\');',
        '$(".add_edit_eval_psiho_modal").css(\'filter\', \'blur(5px)\');' => '$(".add_edit_eval_psiho_modal").addClass(\'js-filter-blur\');',
        '$(".add_edit_consultatie_modal").css(\'filter\', \'blur()\');' => '$(".add_edit_consultatie_modal").removeClass(\'js-filter-blur\');',
        '$(".add_edit_investigatie_modal").css(\'filter\', \'blur()\');' => '$(".add_edit_investigatie_modal").removeClass(\'js-filter-blur\');',
        '$(".add_edit_eval_psiho_modal").css(\'filter\', \'blur()\');' => '$(".add_edit_eval_psiho_modal").removeClass(\'js-filter-blur\');',
        'let color = "style=\'color: black\'";' => 'let color = "js-text-black";',
        'color = "style=\'color: #1cc88a\'";' => 'color = "js-text-success";',
        'color = "style=\'color: #4e73df\'";' => 'color = "js-text-primary";',
        "\"return false;' \" + color + \"' class='istoric_a'>\" + istoricServiciu.denumire + ' | '" =>
            "\"return false;' class='dropdown-item istoric_a \" + color + \"'>\" + istoricServiciu.denumire + ' | '",
        "'<button style=\"width: 40px; height: 40px;\" " => "'<button class=\"js-service-button\" ",
        "'<button style=\"width: 40px; height: 40px; font-weight: bold; \" tip=\"" => "'<button class=\"js-service-button-edit\" tip=\"",
        "'<button style=\"width: 150px; height: 40px; font-size: 14px;\" " => "'<button class=\"js-doc-button\" ",
        "'<button disabled style=\"width: 150px; height: 40px; font-size: 14px;\" " => "'<button disabled class=\"js-doc-button\" ",
    ],
    'consultatii_nefacturate.js' => [
        '$(".consultatii_nefacturate_title").css(\'font-weight\', \'bold\')' => '$(".consultatii_nefacturate_title").addClass(\'js-font-bold\')',
        'style="\' + checkBoxStyle() + \'"' => 'class="\' + checkBoxStyle() + \'"',
        '\'<td class="owner_serviciu" style="font-weight: bold; padding-right: 15px; padding-bottom: 15px;"> \'' =>
            '\'<td class="owner_serviciu js-cell-bold-pad"> \'',
        '\'<td style="font-weight: bold; padding-bottom: 15px;" class="pret_serviciu">\'' =>
            '\'<td class="pret_serviciu js-cell-bold-bottom">\'',
    ],
    'facturi.js' => [
        '$(row).css(\'background-color\', \'#f3eaeb\');' => '$(row).addClass(\'js-row-storno\');',
    ],
    'pacienti.js' => [
        ' $("select[multiple]").css(\'overflow-x\', \'auto\');' => ' $("select[multiple]").addClass(\'js-overflow-x-auto\');',
        '$("select[multiple]").css(\'overflow-x\', \'auto\');' => '$("select[multiple]").addClass(\'js-overflow-x-auto\');',
        '$(".titlu_cons_inv_modal").css(\'font-weight\', \'bold\')' => '$(".titlu_cons_inv_modal").addClass(\'js-font-bold\')',
        '$(".titlu_incasare_modal").css(\'font-weight\', \'bold\').text(' => '$(".titlu_incasare_modal").addClass(\'js-font-bold\').text(',
        '$(\'.tooltip-inner\').css(\'background-color\',\'#d52a1a\')' => '$(\'.tooltip-inner\').removeClass(\'js-tooltip-success\').addClass(\'js-tooltip-danger\')',
        '$(\'.tooltip-inner\').css(\'background-color\',\'#169b6b\')' => '$(\'.tooltip-inner\').removeClass(\'js-tooltip-danger\').addClass(\'js-tooltip-success\')',
        '"style=\'border-left: black solid 1px; border-right: black solid 1px; height: 40px; \'"' => '"class=\'js-service-row-middle\'"',
        '"style=\'border-top: black solid 1px; border-left: black solid 1px; " +' . "\n" . '                            "border-right: black solid 1px; height: 40px;\'"' =>
            '"class=\'js-service-row-first\'"',
        '"style=\'border-bottom: black solid 1px; border-left: black solid 1px; border-right: " +' . "\n" . '                            "black solid 1px; height: 40px;\'"' =>
            '"class=\'js-service-row-last\'"',
        '"style=\'border: black solid 1px; height: 40px;\'"' => '"class=\'js-service-row-single\'"',
        'style=\'" + checkBoxStyle() + "\'' => 'class=\'" + checkBoxStyle() + "\'',
    ],
    'programari.js' => [
        '$("select[multiple]").css(\'overflow-x\', \'auto\');' => '$("select[multiple]").addClass(\'js-overflow-x-auto\');',
        '$(".titlu_cons_inv_modal").css(\'font-weight\', \'bold\')' => '$(".titlu_cons_inv_modal").addClass(\'js-font-bold\')',
    ],
];

$changed = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    $basename = basename($file);

    $content = str_replace(array_keys($commonReplacements), array_values($commonReplacements), $content);

    if (isset($fileReplacements[$basename])) {
        $content = str_replace(array_keys($fileReplacements[$basename]), array_values($fileReplacements[$basename]), $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        $changed[] = str_replace('\\', '/', substr($file, strlen($root) + 1));
    }
}

echo 'CSS: ' . str_replace('\\', '/', substr($cssPath, strlen($root) + 1)) . PHP_EOL;
echo 'Changed JS files: ' . count($changed) . PHP_EOL;
foreach ($changed as $file) {
    echo ' - ' . $file . PHP_EOL;
}
