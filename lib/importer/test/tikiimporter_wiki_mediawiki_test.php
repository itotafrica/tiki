<?php

require_once(dirname(__FILE__) . '/../../core/test/TikiTestCase.php');
require_once(dirname(__FILE__) . '/../tikiimporter_wiki_mediawiki.php');

class TikiImporter_Wiki_Mediawiki_Test extends TikiTestCase 
{

    protected function setUp()
    {
        $this->obj = new TikiImporter_Wiki_Mediawiki;
    }

    public function testImport()
    {
        $parsedData = 'Some text';
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('validateInput', 'parseData', 'insertData'));
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('parseData')->will($this->returnValue($parsedData));
        $obj->expects($this->once())->method('insertData')->with($parsedData);
        $obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $this->assertTrue($obj->dom instanceof DOMDocument);
        $this->assertTrue($obj->dom->hasChildNodes());
    }

    public function testImportWithoutInternalMocking()
    {
        global $tikilib;
        $tikilib = $this->getMock('TikiLib', array('create_page', 'update_page', 'page_exists', 'remove_all_versions'));

        $expectedImportFeedback = array('totalPages' => 4, 'importedPages' => 4);
        $expectedLog = 'Loading and validating the XML file<br /><br />Starting to parse 4 pages:<br />Page "Redes de ensino" succesfully parsed with 8 revisions<br />Page "Núcleo Pedagógico Integrado" succesfully parsed with 15 revisions<br />Page "Categoria:Escolas fictícias" succesfully parsed with 5 revisions<br />Page "Academia Colarossi" succesfully parsed with 2 revisions<br /><br />4 pages parsed. Starting to insert those pages into Tiki:<br />Page Redes de ensino sucessfully imported<br />Page Núcleo Pedagógico Integrado sucessfully imported<br />Page Categoria:Escolas fictícias sucessfully imported<br />Page Academia Colarossi sucessfully imported<br /><br />Importation completed! Please await while the page reloads.';
        
        $this->obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');

        $this->assertTrue($this->obj->dom instanceof DOMDocument);
        $this->assertTrue($this->obj->dom->hasChildNodes());
        $this->assertEquals($expectedImportFeedback, $_SESSION['tiki_importer_feedback']);
        $this->assertEquals($expectedLog, $_SESSION['tiki_importer_log']);
    }

    public function testImportShouldRaiseExceptionForInvalidMimeType()
    {
        require_once(dirname(__FILE__) . '/../../init/tra.php');
        $_FILES['importFile']['type'] = 'invalid/type';
        $this->setExpectedException('UnexpectedValueException');
        $this->obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
    }

    public function testValidateInput()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $this->assertNull($this->obj->validateInput());
    }

    public function testValidateInputShouldRaiseExceptionForInvalidXmlFile()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_invalid.xml');
        $this->setExpectedException('DOMException');
        $this->obj->validateInput();
    }

   public function testParseData()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractInfo'));
        $obj->dom = new DOMDocument;
        $obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $obj->expects($this->exactly(4))->method('extractInfo')->will($this->returnValue(array()));
        $this->assertEquals(4, count($obj->parseData()));
    }

    public function testExtractInfo()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $expectedNames = array('Redes de ensino', 'Academia Colarossi');

        $pages = $dom->getElementsByTagName('page');

        $i = 0;
        foreach ($pages as $page) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
            $obj->expects($this->atLeastOnce())->method('extractRevision')->will($this->returnValue('revision'));

            $return = $obj->extractInfo($page);
            $this->assertEquals($expectedNames[$i++], $return['name']);
            $this->assertGreaterThan(1, count($return['revisions']));
        }
    }

    public function testExtractRevision()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_revision.xml');
        $expectedResult = array(
            array('minor' => false, 'lastModif' => 1139119907, 'ip' => '201.6.123.86', 'user' => 'anonymous', 'comment' => 'fim da tradução', 'data' => 'Some text'),
            array('minor' => false, 'lastModif' => 1176517303, 'user' => 'Girino', 'ip' => '0.0.0.0', 'comment' => 'Revert to revision 5661385', 'data' => 'Some text'));
        $extractContributorReturn = array(
            array('ip' => '201.6.123.86', 'user' => 'anonymous'),
            array('user' => 'Girino', 'ip' => '0.0.0.0'));

        $revisions = $dom->getElementsByTagName('revision');

        $i = 0;
        foreach ($revisions as $revision) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('convertMarkup', 'extractContributor'));
            $obj->expects($this->once())->method('convertMarkup')->will($this->returnValue('Some text'));
            $obj->expects($this->once())->method('extractContributor')->will($this->returnValue($extractContributorReturn[$i]));

            $this->assertEquals($expectedResult[$i++], $obj->extractRevision($revision));
       }
    }

    public function testExtractContributor()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_contributor.xml');
        $expectedResult = array(
            array('user' => 'SomeUserName', 'ip' => '0.0.0.0'),
            array('ip' => '163.117.200.166', 'user' => 'anonymous'),
            array('user' => 'OtherUserName', 'ip' => '0.0.0.0')
        );
        $contributors = $dom->getElementsByTagName('contributor');

        $i = 0;
        foreach ($contributors as $contributor) {
            $this->assertEquals($expectedResult[$i++], $this->obj->extractContributor($contributor));
        }
    }

    // TODO: find a way to mock the Text_Wiki object inside convertMakup()
    public function testConvertMarkup()
    {
        $mediawikiText = '[[someWikiLink]]';
        $expectedResult = "((someWikiLink))\n\n";

        $this->assertEquals($expectedResult, $this->obj->convertMarkup($mediawikiText));
    }

    public function testConvertMarkupShouldReturnNullIfEmptyMediawikiText()
    {
        $mediawikiText = '';
        $this->assertNull($this->obj->convertMarkup($mediawikiText));
    }
}

?>
