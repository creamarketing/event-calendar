<?php

class IM_MemberDecorator extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				'IM_Inbox_EmailNotification' => 'Boolean'
			),
			'defaults' => array(
				'IM_Inbox_EmailNotification' => true
			),
			'has_one' => array(
				'IM_Inbox' => 'IM_MessageBox',
				'IM_Sentbox' => 'IM_MessageBox',
				'IM_Trashbox' => 'IM_MessageBox'
			)
		);
	}
	
	public function updateCMSFields(FieldSet &$fields) {
		$fields->removeByName('IM_InboxID');
		$fields->removeByName('IM_SentboxID');
		$fields->removeByName('IM_TrashboxID');
		$notify = $fields->dataFieldByName('IM_Inbox_EmailNotification');
		if ($notify)
			$notify->setTitle(_t('IM_MemberDecorator.EMAILNOTIFICATION', 'Send copy of internal messages to email'));
	}
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		
		$inbox = $this->owner->IM_Inbox();
		$sentbox = $this->owner->IM_Sentbox();
		$trashbox = $this->owner->IM_Trashbox();
		
		if ($inbox && $inbox->exists())
			$inbox->delete();
		if ($sentbox && $sentbox->exists())
			$sentbox->delete();
		if ($trashbox && $trashbox->exists())
			$trashbox->delete();		
	}
}

?>
