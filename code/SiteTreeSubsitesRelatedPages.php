<?php
class SiteTreeSubsitesRelatedPages extends DataExtension {

	public static $has_one = array(
		'MasterPage' => 'SiteTree'
	);

	public static $has_many = array(
		'RelatedPages' => 'RelatedPageLink'
	);

	public function updateCMSFields(FieldList $fields) {
		$subsites = Subsite::accessible_sites("CMS_ACCESS_CMSMain");
		$subsitesMap = array();
		if($subsites && $subsites->Count()) {
			$subsitesMap = $subsites->map('ID', 'Title');
			unset($subsitesMap[$this->owner->SubsiteID]);
		} 

		// Master page notice
		if($this->owner->MasterPageID) {
			$masterPage = $this->owner->MasterPage();
			$masterNoteField = new LiteralField(
				'MasterLink',
				sprintf(
					_t(
						'SiteTreeSubsites.MasterLinkNote',
						'<p>This page\'s content is copied from the <a href="%s" target="_blank">%s</a> master page (<a href="%s">edit</a>)</p>'
					),
					$masterPage->AbsoluteLink(), 
					$masterPage->Title,
					Controller::join_links(
						singleton('CMSMain')->Link('show'),
						$masterPage->ID
					)
				)
			);
			$fields->addFieldToTab('Root.Main',$masterNoteField);
		}

		// Master page edit field (only allowed from default subsite to avoid inconsistent relationships)
		$isDefaultSubsite = $this->owner->SubsiteID == 0 || $this->owner->Subsite()->DefaultSite;
		if(!($isDefaultSubsite && $subsitesMap)) {
			$defaultSubsite = DataObject::get_one('Subsite', '"DefaultSite" = 1');
			if($defaultSubsite) {
				$fields->addFieldToTab('Root.Main',
					$masterPageField = new SubsitesTreeDropdownField(
						"MasterPageID", 
						_t('VirtualPage.MasterPage', "Master page"), 
						"SiteTree",
						"ID",
						"MenuTitle"
					)
				);
				$masterPageField->setSubsiteID($defaultSubsite->ID);
			}
		}

		$relatedCount = 0;
		$reverse = $this->ReverseRelated();
		if($reverse) $relatedCount += $reverse->Count();
		$normalRelated = $this->NormalRelated();
		if($normalRelated) $relatedCount += $normalRelated->Count();

		$tabName = $relatedCount ? 'Related (' . $relatedCount . ')' : 'Related';
		$tab = $fields->findOrMakeTab('Root.Related', $tabName);
		// Related pages
		$tab->push(new LiteralField('RelatedNote',
			'<p>You can list pages here that are related to this page.<br />When this page is updated, you will get a reminder to check whether these related pages need to be updated as well.</p>'));
		$tab->push(
			$related=new GridField('RelatedPages', 'Related Pages', $this->owner->RelatedPages(), GridFieldConfig_Base::create())
		);
		
		$related->setModelClass('RelatedPageLink');
		
		// The 'show' link doesn't provide any useful info
		//$related->setPermissions(array('add', 'edit', 'delete'));
		
		if($reverse) {
			$text = '<p>In addition, this page is marked as related by the following pages: </p><p>';
			foreach($reverse as $rpage) {
				$text .= $rpage->RelatedPageAdminLink(true) . " - " . $rpage->AbsoluteLink(true) . "<br />\n";
			}
			$text .= '</p>';
			
			$tab->push(new LiteralField('ReverseRelated', $text));
		}
	}

	/**
	 * Returns the RelatedPageLink objects that are reverse-associated with this page.
	 */
	function ReverseRelated() {
		return DataObject::get('RelatedPageLink', "\"RelatedPageLink\".\"RelatedPageID\" = {$this->owner->ID}
			AND R2.\"ID\" IS NULL", '')
			->innerJoin('SiteTree', "\"SiteTree\".\"ID\" = \"RelatedPageLink\".\"MasterPageID\"")
			->leftJoin('RelatedPageLink', "R2.\"MasterPageID\" = {$this->owner->ID} AND R2.\"RelatedPageID\" = \"RelatedPageLink\".\"MasterPageID\"", 'R2');
	}
	
	function NormalRelated() {
		$return = new ArrayList();
		$links = DataObject::get('RelatedPageLink', '"MasterPageID" = ' . $this->owner->ID);
		if($links) foreach($links as $link) {
			if($link->RelatedPage()->exists()) {
				$return->push($link->RelatedPage());
			}
		}
		
		return $return->Count() > 0 ? $return : false;
	}

}
