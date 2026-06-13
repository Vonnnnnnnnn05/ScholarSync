<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SampleCertificateTemplate
{
    public const PATH = 'certificates/samples/certificate-of-no-scholarship-sample.docx';

    public static function ensureExists(): string
    {
        if (Storage::disk('local')->exists(self::PATH)) {
            return self::PATH;
        }

        $absolutePath = Storage::disk('local')->path(self::PATH);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $zip = new ZipArchive;
        $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::relationships());
        $zip->addFromString('word/document.xml', self::document());
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
        $zip->close();

        return self::PATH;
    }

    private static function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;
    }

    private static function relationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    private static function document(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>SULTAN KUDARAT STATE UNIVERSITY</w:t></w:r></w:p>
        <w:p><w:r><w:t>Certificate of No Scholarship</w:t></w:r></w:p>
        <w:p><w:r><w:t>This is a temporary sample DOCX certificate template for ScholarSync development.</w:t></w:r></w:p>
        <w:p><w:r><w:t>The official office-provided certificate format will replace this sample once available.</w:t></w:r></w:p>
        <w:p><w:r><w:t>Student Name: ______________________________</w:t></w:r></w:p>
        <w:p><w:r><w:t>Purpose: ___________________________________</w:t></w:r></w:p>
        <w:p><w:r><w:t>Date Issued: _______________________________</w:t></w:r></w:p>
    </w:body>
</w:document>
XML;
    }
}
