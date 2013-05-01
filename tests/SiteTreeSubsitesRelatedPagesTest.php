<?php
class SiteTreeSubsitesRelatedPagesTest extends SapphireTest {

	static $fixture_file = 'subsites/tests/SubsiteTest.yml';
	
	function testRelatedPages() {
		$this->assertTrue(singleton('RelatedPageLink')->getCMSFields() instanceof FieldList);
		
		$importantpage = $this->objFromFixture('Page', 'importantpage');
		$contact = $this->objFromFixture('Page', 'contact');
		
		$link = new RelatedPageLink();
		$link->MasterPageID = $importantpage->ID;
		$link->RelatedPageID = $contact->ID;
		$link->write();
		$importantpage->RelatedPages()->add($link);
		$this->assertTrue(singleton('SiteTree')->getCMSFields() instanceof FieldList);
		
		$this->assertEquals($importantpage->NormalRelated()->Count(), 1);
		$this->assertEquals($contact->ReverseRelated()->Count(), 1);
		
		$this->assertTrue($importantpage->getCMSFields() instanceof FieldList);
		$this->assertTrue($contact->getCMSFields() instanceof FieldList);
		
		$this->assertEquals($importantpage->canView(), $link->canView());
		$this->assertEquals($importantpage->canEdit(), $link->canEdit());
		$this->assertEquals($importantpage->canDelete(), $link->canDelete());
		$link->AbsoluteLink(true);
		$this->assertEquals($link->RelatedPageAdminLink(), '<a href="admin/pages/edit/show/' . $contact->ID . '" class="cmsEditlink">Contact Us</a>');
	}

}
