<?php
/**
 * A security group.
 * 
 * @package sapphire
 * @subpackage security
 */
class Group extends DataObject {
	
	static $db = array(
		"Title" => "Varchar",
		"Description" => "Text",
		"Code" => "Varchar",
		"Locked" => "Boolean",
		"Sort" => "Int",
		"IPRestrictions" => "Text",
		"HtmlEditorConfig" => "Varchar"
	);
	
	static $has_one = array(
		"Parent" => "Group",
	);
	
	static $has_many = array(
		"Permissions" => "Permission",
		"Groups" => "Group"
	);
	
	static $many_many = array(
		"Members" => "Member",
		"Roles" => "PermissionRole",
	);
	
	static $extensions = array(
		"Hierarchy",
	);
	
	function populateDefaults() {
		parent::populateDefaults();
		
		if(!$this->Title) $this->Title = _t('SecurityAdmin.NEWGROUP',"New Group");
	}
	
	function getAllChildren() {
		$doSet = new ArrayList();

		if ($children = DataObject::get('Group', '"ParentID" = '.$this->ID)) {
			foreach($children as $child) {
				$doSet->push($child);
				$doSet->merge($child->getAllChildren());
			}
		}
		
		return $doSet;
	}
	
	/**
	 * Caution: Only call on instances, not through a singleton.
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/PermissionCheckboxSetField.js');
		
		$config = new GridFieldConfig_ManyManyEditor('FirstName', true, 20);
		$config->addComponent(new GridFieldExporter());
		$memberList = new GridField('Members','Members', $this->Members(), $config);

		// @todo Implement permission checking on GridField
		//$memberList->setPermissions(array('edit', 'delete', 'export', 'add', 'inlineadd'));
		//$memberList->setPopupCaption(_t('SecurityAdmin.VIEWUSER', 'View User'));
		$fields = new FieldList(
			new TabSet("Root",
				new Tab('Members', _t('SecurityAdmin.MEMBERS', 'Members'),
					new TextField("Title", $this->fieldLabel('Title')),
					$memberList
				),

				$permissionsTab = new Tab('Permissions', _t('SecurityAdmin.PERMISSIONS', 'Permissions'),
					new PermissionCheckboxSetField(
						'Permissions',
						false,
						'Permission',
						'GroupID',
						$this
					)
				),

				new Tab('IPAddresses', _t('Security.IPADDRESSES', 'IP Addresses'),
					new LiteralField("", _t('SecurityAdmin.IPADDRESSESHELP',"<p>You can restrict this group to a particular 
						IP address range (one range per line). <br />Ranges can be in any of the following forms: <br />
						203.96.152.12<br />
						203.96.152/24<br />
						203.96/16<br />
						203/8<br /><br />If you enter one or more IP address ranges in this box, then members will only get
						the rights of being in this group if they log on from one of the valid IP addresses.  It won't prevent
						people from logging in.  This is because the same user might have to log in to access parts of the
						system without IP address restrictions.")),
					new TextareaField("IPRestrictions", "IP Ranges", 10)
				)
			)
		);
		
		// Only add a dropdown for HTML editor configurations if more than one is available.
		// Otherwise Member->getHtmlEditorConfigForCMS() will default to the 'cms' configuration.
		$editorConfigMap = HtmlEditorConfig::get_available_configs_map();
		if(count($editorConfigMap) > 1) {
			$fields->addFieldToTab('Root.Permissions',
				new DropdownField(
					'HtmlEditorConfig', 
					'HTML Editor Configuration', 
					$editorConfigMap
				),
				'Permissions'
			);
		}

		if(!Permission::check('EDIT_PERMISSIONS')) {
			$fields->removeFieldFromTab('Root', 'Permissions');
			$fields->removeFieldFromTab('Root', 'IP Addresses');
		}

		// Only show the "Roles" tab if permissions are granted to edit them,
		// and at least one role exists
		if(Permission::check('APPLY_ROLES') && DataObject::get('PermissionRole')) { 
			$fields->findOrMakeTab('Root.Roles', _t('SecurityAdmin.ROLES', 'Roles'));
			$fields->addFieldToTab('Root.Roles', 
				new LiteralField( 
					"",  
					"<p>" .  
					_t('SecurityAdmin.ROLESDESCRIPTION', 
						"This section allows you to add roles to this group. Roles are logical groupings of permissions, which can be editied in the Roles tab" 
					) .  
					 "</p>" 
				) 
			);
			
			// Add roles (and disable all checkboxes for inherited roles)
			$allRoles = Permission::check('ADMIN') ? DataObject::get('PermissionRole') : DataObject::get('PermissionRole', 'OnlyAdminCanApply = 0');
			$groupRoles = $this->Roles();
			$inheritedRoles = new ArrayList();
			$ancestors = $this->getAncestors();
			foreach($ancestors as $ancestor) {
				$ancestorRoles = $ancestor->Roles();
				if($ancestorRoles) $inheritedRoles->merge($ancestorRoles);
			}
			$fields->findOrMakeTab('Root.Roles', 'Root.' . _t('SecurityAdmin.ROLES', 'Roles'));
			$fields->addFieldToTab(
				'Root.Roles',
				$rolesField = new CheckboxSetField('Roles', 'Roles', $allRoles)
			);
			$rolesField->setDefaultItems($inheritedRoles->column('ID'));
			$rolesField->setDisabledItems($inheritedRoles->column('ID'));
		} 
		
		$fields->push($idField = new HiddenField("ID"));
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['Title'] = _t('SecurityAdmin.GROUPNAME', 'Group name');
		$labels['Description'] = _t('Group.Description', 'Description');
		$labels['Code'] = _t('Group.Code', 'Group Code', PR_MEDIUM, 'Programmatical code identifying a group');
		$labels['Locked'] = _t('Group.Locked', 'Locked?', PR_MEDIUM, 'Group is locked in the security administration area');
		$labels['Sort'] = _t('Group.Sort', 'Sort Order');
		$labels['IPRestrictions'] = _t('Group.IPRestrictions', 'IP Address Restrictions');
		if($includerelations){
			$labels['Parent'] = _t('Group.Parent', 'Parent Group', PR_MEDIUM, 'One group has one parent group');
			$labels['Permissions'] = _t('Group.has_many_Permissions', 'Permissions', PR_MEDIUM, 'One group has many permissions');
			$labels['Members'] = _t('Group.many_many_Members', 'Members', PR_MEDIUM, 'One group has many members');
		}
		
		return $labels;
	}
	
	/**
	 * @deprecated 2.5
	 */
	public static function addToGroupByName($member, $groupcode) {
		Deprecation::notice('2.5', 'Use $member->addToGroupByCode($groupcode) instead.');
		
		return $member->addToGroupByCode($groupcode);
	}
	
	/**
	 * Overloaded getter.
	 *
	 * @TODO Where is this used, why is this overloaded?
	 * 
	 * @param $limit string SQL
	 * @param $offset int
	 * @param $filter string SQL
	 * @param $sort string SQL
	 * @param $join string SQL
	 * @return ComponentSet
	 */
	public function Members($filter = "", $sort = "", $join = "", $limit = "") {
		// Get a DataList of the relevant groups
		$groups = DataList::create("Group")->byIDs($this->collateFamilyIDs());
		
		if($sort || $join || $limit) {
			Deprecation::notice('3.0', "The sort, join, and limit arguments are deprcated, use sort(), join() and limit() on the resulting DataList instead.");
		}

		// Call the relation method on the DataList to get the members from all the groups
		$result = $groups->relation('DirectMembers')->where($filter)->sort($sort)->limit($limit);
		if($join) $result = $result->join($join);

		return $result;
	}
	
	/**
	 * Return only the members directly added to this group
	 */
	public function DirectMembers() {
		return $this->getManyManyComponents('Members');
	}
	
	public static function map($filter = "", $sort = "", $blank="") {
		Deprecation::notice('3.0', 'Use DataList::("Group")->map()');

		$list = DataList::create("Group")->where($filter)->sort($sort);
		$map = $list->map();

		if($blank) $map->unshift(0, $blank);
		
		return $map;
	}
	
	/**
	 * Return a set of this record's "family" of IDs - the IDs of
	 * this record and all its descendants.
	 * @return array
	 */
	public function collateFamilyIDs() {
		$familyIDs = array();
		$chunkToAdd = array(array("ID" => $this->ID));
		
		while($chunkToAdd) {
			$idList = array();
			foreach($chunkToAdd as $item) {
				$idList[] = $item['ID'];
				$familyIDs[] = $item['ID'];
			}
			$idList = implode(',', $idList);
			
			// Get the children of *all* the groups identified in the previous chunk.
			// This minimises the number of SQL queries necessary			
			$chunkToAdd = DataList::create('Group')->where("\"ParentID\" IN ($idList)")->column('ID');
		}
		
		return $familyIDs;
	}
	
	/**
	 * Returns an array of the IDs of this group and all its parents
	 */
	public function collateAncestorIDs() {
		$parent = $this;
		while(isset($parent) && $parent instanceof Group) {
			$items[] = $parent->ID;
			$parent = $parent->Parent;
		}
		return $items;
	}
	
	/**
	 * This isn't a decendant of SiteTree, but needs this in case
	 * the group is "reorganised";
	 */
	function cmsCleanup_parentChanged() {
	}
	
	/**
	 * Override this so groups are ordered in the CMS
	 */
	public function stageChildren() {
		return DataObject::get('Group', "\"Group\".\"ParentID\" = " . (int)$this->ID . " AND \"Group\".\"ID\" != " . (int)$this->ID, '"Sort"');
	}
	
	/**
	 * @deprecated 3.0 Use getTreeTitle()
	 */
	function TreeTitle() {
		Deprecation::notice('3.0', 'Use getTreeTitle() instead.');
		return $this->getTreeTitle();
	}
	
	public function getTreeTitle() {
	    if($this->hasMethod('alternateTreeTitle')) return $this->alternateTreeTitle();
		else return htmlspecialchars($this->Title, ENT_QUOTES);
	}
	
	/**
	 * Overloaded to ensure the code is always descent.
	 * 
	 * @param string
	 */
	public function setCode($val){
		$this->setField("Code", Convert::raw2url($val));
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		
		// Only set code property when the group has a custom title, and no code exists.
		// The "Code" attribute is usually treated as a more permanent identifier than database IDs
		// in custom application logic, so can't be changed after its first set.
		if(!$this->Code && $this->Title != _t('SecurityAdmin.NEWGROUP',"New Group")) {
			if(!$this->Code) $this->setCode($this->Title);
		}
	}
	
	function onAfterDelete() {
		parent::onAfterDelete();
		
		// Delete associated permissions
		$permissions = $this->Permissions();
		foreach ( $permissions as $permission ) {
			$permission->delete();
		}
	}
	
	/**
	 * Checks for permission-code CMS_ACCESS_SecurityAdmin.
	 * If the group has ADMIN permissions, it requires the user to have ADMIN permissions as well.
	 * 
	 * @param $member Member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// extended access checks
		$results = $this->extend('canEdit', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
 		if(
			// either we have an ADMIN
			(bool)Permission::checkMember($member, "ADMIN")
			|| (
				// or a privileged CMS user and a group without ADMIN permissions.
				// without this check, a user would be able to add himself to an administrators group
				// with just access to the "Security" admin interface
				Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin") && 
				!DataObject::get("Permission", "GroupID = $this->ID AND Code = 'ADMIN'")
			)
		) {
			return true;
		}

		return false;
	}
	
	/**
	 * Checks for permission-code CMS_ACCESS_SecurityAdmin.
	 * 
	 * @param $member Member
	 * @return boolean
	 */
	public function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// extended access checks
		$results = $this->extend('canView', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		// user needs access to CMS_ACCESS_SecurityAdmin
		if(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin")) return true;
		
		return false;
	}
	
	public function canDelete($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// extended access checks
		$results = $this->extend('canDelete', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return $this->canEdit($member);
	}

	/**
	 * Returns all of the children for the CMS Tree.
	 * Filters to only those groups that the current user can edit
	 */
	function AllChildrenIncludingDeleted() {
		$extInstance = $this->getExtensionInstance('Hierarchy');
		$extInstance->setOwner($this);
		$children = $extInstance->AllChildrenIncludingDeleted();
		$extInstance->clearOwner();
		
		$filteredChildren = new ArrayList();
		
		if($children) foreach($children as $child) {
			if($child->canView()) $filteredChildren->push($child);
		}
		
		return $filteredChildren;
	}
	
	/**
	 * Returns true if the given IP address is granted access to this group.
	 * For unrestricted groups, this always returns true.
	 */
	function allowedIPAddress($ip) {
		if(!$this->IPRestrictions) return true;
		if(!$ip) return false;
		
		$ipPatterns = explode("\n", $this->IPRestrictions);
		foreach($ipPatterns as $ipPattern) {
			$ipPattern = trim($ipPattern);
			if(preg_match('/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/', $ipPattern, $matches)) {
				if($ip == $ipPattern) return true;
			} else if(preg_match('/^([0-9]+\.[0-9]+\.[0-9]+)\/24$/', $ipPattern, $matches)
					|| preg_match('/^([0-9]+\.[0-9]+)\/16$/', $ipPattern, $matches)
					|| preg_match('/^([0-9]+)\/8$/', $ipPattern, $matches)) {
				if(substr($ip, 0, strlen($matches[1])) == $matches[1]) return true;
			}
		}
		return false;
	}
	
	/**
	 * Add default records to database.
	 *
	 * This function is called whenever the database is built, after the
	 * database tables have all been created.
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		// Add default author group if no other group exists
		$allGroups = DataObject::get('Group');
		if(!$allGroups->count()) {
			$authorGroup = new Group();
			$authorGroup->Code = 'content-authors';
			$authorGroup->Title = _t('Group.DefaultGroupTitleContentAuthors', 'Content Authors');
			$authorGroup->Sort = 1;
			$authorGroup->write();
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_CMSMain');
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_AssetAdmin');
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_CommentAdmin');
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_ReportAdmin');
			Permission::grant($authorGroup->ID, 'SITETREE_REORGANISE');
		}
	
		// Add default admin group if none with permission code ADMIN exists
		$adminGroups = Permission::get_groups_by_permission('ADMIN');
		if(!$adminGroups->count()) {
			$adminGroup = new Group();
			$adminGroup->Code = 'administrators';
			$adminGroup->Title = _t('Group.DefaultGroupTitleAdministrators', 'Administrators');
			$adminGroup->Sort = 0;
			$adminGroup->write();
			Permission::grant($adminGroup->ID, 'ADMIN');
		}		
		
		// Members are populated through Member->requireDefaultRecords()
	}

	/**
	 * @return String
	 */
	function CMSTreeClasses($controller) {
		$classes = sprintf('class-%s', $this->class);

		if(!$this->canDelete())
			$classes .= " nodelete";

		if($controller->isCurrentPage($this))
			$classes .= " current";

		if(!$this->canEdit()) 
			$classes .= " disabled";
			
		$classes .= $this->markingClasses();

		return $classes;
	}
}
	

