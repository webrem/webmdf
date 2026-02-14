<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


namespace Smalot\PdfParser\Encoding;

abstract class AbstractEncoding
{
    abstract public function getTranslations(): array;
}
