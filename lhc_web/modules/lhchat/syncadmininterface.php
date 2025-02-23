<?php
header ( 'content-type: application/json; charset=utf-8' );
$currentUser = erLhcoreClassUser::instance();

$onlineTimeout = (int)erLhcoreClassModelChatConfig::fetchCache('sync_sound_settings')->data['online_timeout'];
erLhcoreClassChat::$trackActivity = (int)erLhcoreClassModelChatConfig::fetchCache('track_activity')->current_value == 1;
erLhcoreClassChat::$trackTimeout = (int)erLhcoreClassModelChatConfig::fetchCache('checkstatus_timeout')->current_value;
erLhcoreClassChat::$onlineCondition = (int)erLhcoreClassModelChatConfig::fetchCache('online_if')->current_value;

$canListOnlineUsers = false;
$canListOnlineUsersAll = false;

if (erLhcoreClassModelChatConfig::fetchCache('list_online_operators')->current_value == 1) {
	$canListOnlineUsers = $currentUser->hasAccessTo('lhuser','userlistonline');
	$canListOnlineUsersAll = $currentUser->hasAccessTo('lhuser','userlistonlineall');
}

// Update last visit
$currentUser->updateLastVisit((int)$Params['user_parameters_unordered']['lda']);

$userData = $currentUser->getUserData(true);

if ($userData->force_logout == 1) {
    $currentUser->logout();
    $userData->force_logout = 0;
    $userData->updateThis(['update' => ['force_logout']]);
    echo erLhcoreClassChat::safe_json_encode(array('logout' => true));
    exit;
}

// We do not need a session anymore
session_write_close();

$columnsAdditional = erLhAbstractModelChatColumn::getList(array('ignore_fields' => array('position','conditions','column_name','column_name','column_identifier','enabled'), 'sort' => false, 'filter' => array('enabled' => 1, 'chat_enabled' => 1)));

// User has included custom column which we ignore by default
foreach ($columnsAdditional as $columnAdditional) {
    if (strpos($columnAdditional->variable,'lhc.') !== false) {
        $variableName = str_replace('lhc.','', $columnAdditional->variable);
        if (in_array($variableName,erLhcoreClassChat::$chatListIgnoreField)) {
            unset(erLhcoreClassChat::$chatListIgnoreField[array_search($variableName,erLhcoreClassChat::$chatListIgnoreField)]);
        }
    }
}

$ReturnMessages = array();

$pendingTabEnabled = erLhcoreClassModelUserSetting::getSetting('enable_pending_list',1);
$activeTabEnabled = erLhcoreClassModelUserSetting::getSetting('enable_active_list',1);

if (erLhcoreClassModelChatConfig::fetchCache('list_closed')->current_value == 1) {
    $closedTabEnabled = erLhcoreClassModelUserSetting::getSetting('enable_close_list',0);
} else {
    $closedTabEnabled = 0;
}

$botTabEnabled = erLhcoreClassModelUserSetting::getSetting('enable_bot_list',1);

if (erLhcoreClassModelChatConfig::fetchCache('list_unread')->current_value == 1) {
    $unreadTabEnabled = erLhcoreClassModelUserSetting::getSetting('enable_unread_list', 0);
} else {
    $unreadTabEnabled = 0;
}

$showAllPending = erLhcoreClassModelUserSetting::getSetting('show_all_pending',1);
$showDepartmentsStats = $currentUser->hasAccessTo('lhuser','canseedepartmentstats');
$showDepartmentsStatsAll = $currentUser->hasAccessTo('lhuser','canseealldepartmentstats');
$myChatsEnabled = erLhcoreClassModelUserSetting::getSetting('enable_mchats_list',1);

$chatsList = array();
$chatsListAll = array();

$mapsWidgets = [
    'my_chats' => 0,
    'subject_chats' => 20,
    'online_operators' => 1,
    'group_chats' => 2,
    'pending_chats' => 3,
    'online_visitors' => 4,
    'unread_chats' => 5,
    'active_chats' => 6,
    'bot_chats' => 7,
    'transfered_chats' => 8,
    'departments_stats' => 9,
];

if (is_array($Params['user_parameters_unordered']['w']) && in_array($mapsWidgets['subject_chats'],$Params['user_parameters_unordered']['w']) && $currentUser->hasAccessTo('lhchat', 'subject_chats') == true) {

    $limitList = is_numeric($Params['user_parameters_unordered']['limits']) ? (int)$Params['user_parameters_unordered']['limits'] : 10;

    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

    if (is_array($Params['user_parameters_unordered']['subjectd']) && !empty($Params['user_parameters_unordered']['subjectd'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['subjectd']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['subjectd'];
    }

    if (is_array($Params['user_parameters_unordered']['sdgroups']) && !empty($Params['user_parameters_unordered']['sdgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['sdgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['sdgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }

    if (is_array($Params['user_parameters_unordered']['subjectdprod']) && !empty($Params['user_parameters_unordered']['subjectdprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['subjectdprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['subjectdprod'];
    }

    if (is_array($Params['user_parameters_unordered']['subjectu']) && !empty($Params['user_parameters_unordered']['subjectu'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['subjectu']);
        $filter['filterin']['user_id'] = $Params['user_parameters_unordered']['subjectu'];
    }

    // User groups filter
    if (is_array($Params['user_parameters_unordered']['sugroups']) && !empty($Params['user_parameters_unordered']['sugroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['sugroups']);
        $userIds = erLhcoreClassChat::getUserIDByGroup($Params['user_parameters_unordered']['sugroups']);
        if (!empty($userIds)) {
            $filter['filterin']['user_id'] = isset($additionalFilter['filterin']['user_id']) ? array_merge($additionalFilter['filterin']['user_id'],$userIds) : $userIds;
        }
    }

    $chats = erLhcoreClassChat::getSubjectChats($limitList, 0, $filter);

    if (!empty($chats)) {
        $subjectsSelected = erLhAbstractModelSubjectChat::getList(array('filter' => array('chat_id' => array_keys($chats))));
        $subjectByChat = [];
        $subject_ids = [];
        foreach ($subjectsSelected as $subjectSelected) {
            $subject_ids[] = $subjectSelected->subject_id;
        }
        if (!empty($subject_ids)) {
            $subjectsMeta = erLhAbstractModelSubject::getList(array('filterin' => array('id' => array_unique($subject_ids))));
        }
        foreach ($subjectsSelected as $subjectSelected) {
            if (isset( $subjectsMeta[$subjectSelected->subject_id])) {
                $subjectByChat[$subjectSelected->chat_id][] = $subjectsMeta[$subjectSelected->subject_id]->name;
            }
        }
    }

    erLhcoreClassChat::prefillGetAttributes($chats, array('user_status_front','hum','time_created_front','department_name','plain_user_name','product_name','n_official','pnd_rsp','n_off_full','aicons','last_msg_time_front','start_last_action_front'),array('last_op_msg_time','has_unread_messages','product_id','product','department','time','pnd_time','user_id','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));

    foreach ($chats as $index => $chat) {
        if (isset($subjectByChat[$chat->id])) {
            $chats[$index]->subject_list = $subjectByChat[$chat->id];
        }
    }

    $ReturnMessages['subject_chats'] = array('last_id_identifier' => 'subject_chats', 'list' => array_values($chats));
}

if ($showDepartmentsStats == true && is_array($Params['user_parameters_unordered']['w']) && in_array($mapsWidgets['departments_stats'],$Params['user_parameters_unordered']['w'])) {
    /**
     * Departments stats
     * */
    $limitList = is_numeric($Params['user_parameters_unordered']['limitd']) ? (int)$Params['user_parameters_unordered']['limitd'] : 10;


    if (!(is_array($Params['user_parameters_unordered']['hsub']) && in_array('dhdep',$Params['user_parameters_unordered']['hsub']))){
        $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

        $filter['limit'] = $limitList;

        if (is_array($Params['user_parameters_unordered']['departmentd']) && !empty($Params['user_parameters_unordered']['departmentd'])) {
            erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['departmentd']);
            $filter['filterin']['id'] = $Params['user_parameters_unordered']['departmentd'];
        }

        if (is_array($Params['user_parameters_unordered']['ddgroups']) && !empty($Params['user_parameters_unordered']['ddgroups'])) {
            erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['ddgroups']);
            $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['ddgroups']);
            if (!empty($depIds)) {
                $filter['filterin']['id'] = isset($filter['filterin']['id']) ? array_merge($filter['filterin']['id'],$depIds) : $depIds;
            }
        }

        // Add permission check if operator does not have permission to see all departments stats
        if ($showDepartmentsStatsAll === false) {

            if ( $userData->all_departments == 0 )
            {
                $userDepartaments = erLhcoreClassUserDep::getUserDepartaments($currentUser->getUserID(), $userData->cache_version);
                if (!empty($userDepartaments)) {
                    if ( isset( $filter['filterin']['id']) ) {
                        $validDepartments = array_intersect($userDepartaments, $filter['filterin']['id']);
                        if (!empty($validDepartments)) {
                            $filter['filterin']['id'] = $validDepartments;
                        } else {
                            $filter['filterin']['id'] = array(-1);
                        }
                    } else {
                        $filter['filterin']['id'] = $userDepartaments;
                    }
                } else {
                    $filter['filterin']['id'] = array(-1); // No departments
                }
            }
        }

        $filter['sort'] = 'active_chats_counter DESC';

        $departments = erLhcoreClassModelDepartament::getList($filter);

        erLhcoreClassChat::prefillGetAttributes($departments,array('id', 'name', 'pending_chats_counter', 'active_chats_counter', 'bot_chats_counter', 'inop_chats_cnt', 'acop_chats_cnt', 'inactive_chats_cnt', 'max_load','max_load_h'), array(), array('remove_all' => true));

        $ReturnMessages['departments_stats'] = array('list' => array_values($departments));
    }

    if (!(is_array($Params['user_parameters_unordered']['hsub']) && in_array('dhdepg', $Params['user_parameters_unordered']['hsub']))){
        // Departments groups stats
        $limitList = is_numeric($Params['user_parameters_unordered']['limitd']) ? (int)$Params['user_parameters_unordered']['limitd'] : 10;

        $filter = array();
        $filter['limit'] = $limitList;

        if (is_array($Params['user_parameters_unordered']['ddgroups']) && !empty($Params['user_parameters_unordered']['ddgroups'])) {
            erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['ddgroups']);
            $filter['filterin']['id'] = $Params['user_parameters_unordered']['ddgroups'];
        }

        $filter['sort'] = 'achats_cnt DESC';

        $departmentsGroups = erLhcoreClassModelDepartamentGroup::getList($filter);
        erLhcoreClassChat::prefillGetAttributes($departmentsGroups,array('id', 'name', 'achats_cnt', 'pchats_cnt', 'bchats_cnt', 'inopchats_cnt', 'acopchats_cnt','inachats_cnt', 'max_load','max_load_h' ), array(), array('remove_all' => true));

        $ReturnMessages['depgroups_stats'] = array('list' => array_values($departmentsGroups));
    }

}

$chatsForced = array();

if ($activeTabEnabled == true) {
	/**
	 * Active chats
	 * */
    $limitList = is_numeric($Params['user_parameters_unordered']['limita']) ? (int)$Params['user_parameters_unordered']['limita'] : 10;
    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);
    
    if (is_array($Params['user_parameters_unordered']['actived']) && !empty($Params['user_parameters_unordered']['actived'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['actived']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['actived'];
    }

    if (is_array($Params['user_parameters_unordered']['activedprod']) && !empty($Params['user_parameters_unordered']['activedprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['activedprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['activedprod'];
    }
    
    if (is_array($Params['user_parameters_unordered']['activeu']) && !empty($Params['user_parameters_unordered']['activeu'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['activeu']);
        $filter['filterin']['user_id'] = $Params['user_parameters_unordered']['activeu'];
    }   

    if (is_array($Params['user_parameters_unordered']['adgroups']) && !empty($Params['user_parameters_unordered']['adgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['adgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['adgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }

    // User groups filter
    if (is_array($Params['user_parameters_unordered']['augroups']) && !empty($Params['user_parameters_unordered']['augroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['augroups']);
        $userIds = erLhcoreClassChat::getUserIDByGroup($Params['user_parameters_unordered']['augroups']);
        if (!empty($userIds)) {
            $filter['filterin']['user_id'] = isset($filter['filterin']['user_id']) ? array_merge($filter['filterin']['user_id'],$userIds) : $userIds;
        }
    }
    
    $sortArray = array(
        'op_asc' => 'user_id ASC',
        'op_dsc' => 'user_id DESC',
        'dep_asc' => 'dep_id ASC',
        'dep_dsc' => 'dep_id DESC',
        'id_asc' => 'id ASC',
        'id_dsc' => 'id DESC',
        'lmt_asc' => 'last_msg_id ASC',
        'lmt_dsc' => 'last_msg_id DESC',
        'loc_dsc' => 'country_code DESC',
        'loc_asc' => 'country_code ASC',
        'u_dsc' => 'nick DESC',
        'u_asc' => 'nick ASC'
    );

    if (!empty($Params['user_parameters_unordered']['acs']) && key_exists($Params['user_parameters_unordered']['acs'], $sortArray)) {
        $filter['sort'] = $sortArray[$Params['user_parameters_unordered']['acs']];
    }
    
	$chats = erLhcoreClassChat::getActiveChats($limitList,0,$filter);

    $chatsListAll = $chatsListAll+$chats;

	// Collect chats which were transfered
	$lastTransferedForceId = 0;
	$transferedArray = array();
	foreach ($chats as $chat)
	{
	   if ($chat->status_sub_sub == erLhcoreClassModelChat::STATUS_SUB_SUB_TRANSFERED && $chat->user_id == $userData->id) {
	       $chat->status_sub_sub = erLhcoreClassModelChat::STATUS_SUB_SUB_DEFAULT;
	       $chatsForced[] = array(
	           'id' => $chat->id,
	           'nick' => $chat->nick,
	       );
	       $transferedArray[] = $chat->id;
	   }
	}

	if (!empty($transferedArray)) {
	    $db = ezcDbInstance::get();
	    $db->query('UPDATE `lh_chat` SET `status_sub_sub` = 0 WHERE `id` IN (' . implode(',', $transferedArray) . ')');
	}

	erLhcoreClassChat::prefillGetAttributes($chats,array('user_status_front','hum','time_created_front','department_name','plain_user_name','product_name','n_official','pnd_rsp','n_off_full','aicons','last_msg_time_front'),array('last_op_msg_time','has_unread_messages','product_id','product','department','time','pnd_time','status','user_id','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));
	$ReturnMessages['active_chats'] = array('last_id_identifier' => 'active_chats', 'list' => array_values($chats));
	$chatsList[] = & $ReturnMessages['active_chats']['list'];
}

if ($currentUser->hasAccessTo('lhgroupchat','use')) {
    $limitList = is_numeric($Params['user_parameters_unordered']['limitgc']) ? (int)$Params['user_parameters_unordered']['limitgc'] : 10;

    $chats = erLhcoreClassModelGroupChat::getList(array('limit' => $limitList, 'filter' => array('type' => 0)));

    $memberOf = erLhcoreClassModelGroupChatMember::getList(array('sort' => 'jtime ASC', 'filter' => array('type' => erLhcoreClassModelGroupChatMember::NORMAL_CHAT, 'user_id' => $currentUser->getUserID())));

    $groupsPrivates = array();
    $groupsPrivateMembers = array();

    foreach ($memberOf as $member) {
        if (!isset($chats[$member->group_id])) {
            $groupsPrivates[] = $member->group_id;
        }
        $groupsPrivateMembers[$member->group_id] = $member;
    }

    if (!empty($groupsPrivates)) {
        $chatsPrivate = erLhcoreClassModelGroupChat::getList(array('limit' => $limitList, 'filter' => array('type' => erLhcoreClassModelGroupChat::PRIVATE_CHAT), 'filterin' => array('id' => $groupsPrivates)));
        $chats = $chatsPrivate + $chats;
    }

    foreach ($chats as $indexChat => $chat) {
        $chats[$indexChat]->member = isset($groupsPrivateMembers[$chat->id]) ? $groupsPrivateMembers[$chat->id] : null;
    }

    erLhcoreClassChat::prefillGetAttributes($chats, array('time_front', 'jtime', 'is_member', 'ls_id', 'last_msg_id'), array('member','time','status','last_msg_op_id','last_msg'), array('do_not_clean' => true,'clean_ignore' => true));

    $ReturnMessages['group_chats'] = array('list' => array_values($chats));

    $memberOfSupportChat = erLhcoreClassModelGroupChatMember::getList(array('sort' => 'jtime ASC', 'filter' => array('type' => erLhcoreClassModelGroupChatMember::SUPPORT_CHAT, 'jtime' => 0, 'user_id' => $currentUser->getUserID())));
    if (!empty($memberOfSupportChat)) {
        $supportChats = [];
        foreach ($memberOfSupportChat as $supportChatMember){
            $supportChats[] = $supportChatMember->group_id;
        }
        if (!empty($supportChats)) {
            $supportGroupChats = erLhcoreClassModelGroupChat::getList(array('filterin' => array('id' => $supportChats)));
            foreach ($supportGroupChats as $supportChat) {
                $ReturnMessages['support_chats']['list'][] = ['chat_id' => $supportChat->chat_id];
            }
        }
    }
}

if ($myChatsEnabled == true) {
    /**
     * My chats chats
     * */
    $limitList = is_numeric($Params['user_parameters_unordered']['limitmc']) ? (int)$Params['user_parameters_unordered']['limitmc'] : 10;
    
    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

    if (is_array($Params['user_parameters_unordered']['mcd']) && !empty($Params['user_parameters_unordered']['mcd'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['mcd']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['mcd'];
    }
    
    if (is_array($Params['user_parameters_unordered']['mcdprod']) && !empty($Params['user_parameters_unordered']['mcdprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['mcdprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['mcdprod'];
    }

    if (is_array($Params['user_parameters_unordered']['mdgroups']) && !empty($Params['user_parameters_unordered']['mdgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['mdgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['mdgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }
    
    $filter['filter']['user_id'] = (int)$currentUser->getUserID();
    
    $myChats = erLhcoreClassChat::getMyChats($limitList,0,$filter);

    $chatsListAll = $chatsListAll+$myChats;

    /**
     * Get last pending chat
     * */
    erLhcoreClassChat::prefillGetAttributes($myChats,array('user_status_front','hum','time_created_front','product_name','department_name','pnd_rsp','last_msg_time_front','wait_time_pending','wait_time_seconds','plain_user_name','aicons'), array('last_op_msg_time','has_unread_messages','product_id','product','department','time','pnd_time','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));
    
    $ReturnMessages['my_chats'] = array('list' => array_values($myChats));
    
    $chatsList[] = & $ReturnMessages['my_chats']['list'];
}

if ($closedTabEnabled == true) {

    $limitList = is_numeric($Params['user_parameters_unordered']['limitc']) ? (int)$Params['user_parameters_unordered']['limitc'] : 10;

    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

    if (is_array($Params['user_parameters_unordered']['closedd']) && !empty($Params['user_parameters_unordered']['closedd'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['closedd']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['closedd'];
    }

    if (is_array($Params['user_parameters_unordered']['closeddprod']) && !empty($Params['user_parameters_unordered']['closeddprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['closeddprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['closeddprod'];
    }

    if (is_array($Params['user_parameters_unordered']['cdgroups']) && !empty($Params['user_parameters_unordered']['cdgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['cdgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['cdgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }

    $sortArray = array(
        'id_asc' => 'id ASC',
        'id_dsc' => 'id DESC',
        'cst_asc' => 'cls_time ASC',
        'cst_dsc' => 'cls_time DESC',

    );

    if (!empty($Params['user_parameters_unordered']['clcs']) && key_exists($Params['user_parameters_unordered']['clcs'], $sortArray)) {
        $filter['sort'] = $sortArray[$Params['user_parameters_unordered']['clcs']];
    }

	/**
	 * Closed chats
	 * */
	$chats = erLhcoreClassChat::getClosedChats($limitList,0,$filter);

	$chatsListAll = $chatsListAll+$chats;

	erLhcoreClassChat::prefillGetAttributes($chats,array('user_status_front','cls_time_front', 'time_created_front','department_name','plain_user_name','product_name'),array('product_id','product','department','time','status','user_id','user','additional_data','additional_data_array','chat_variables','chat_variables_array','last_op_msg_time','pnd_time'),array('additional_columns' => $columnsAdditional));
	$ReturnMessages['closed_chats'] = array('list' => array_values($chats));
	
	$chatsList[] = & $ReturnMessages['closed_chats']['list'];
}

if (is_array($Params['user_parameters_unordered']['w']) && in_array($mapsWidgets['bot_chats'], $Params['user_parameters_unordered']['w']) && $botTabEnabled == true) {
    $limitList = is_numeric($Params['user_parameters_unordered']['limitb']) ? (int)$Params['user_parameters_unordered']['limitb'] : 10;

    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

    if (is_array($Params['user_parameters_unordered']['botd']) && !empty($Params['user_parameters_unordered']['botd'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['botd']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['botd'];
    }

    if (is_array($Params['user_parameters_unordered']['botdprod']) && !empty($Params['user_parameters_unordered']['botdprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['botdprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['botdprod'];
    }

    if (is_array($Params['user_parameters_unordered']['bdgroups']) && !empty($Params['user_parameters_unordered']['bdgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['bdgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['bdgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }

    /**
     * Bot chats
     * */
    $chats = erLhcoreClassChat::getBotChats($limitList,0,$filter);

    $chatsListAll = $chatsListAll+$chats;

    erLhcoreClassChat::prefillGetAttributes($chats,array('user_status_front','time_created_front','department_name','plain_user_name','product_name','msg_v','aicons','aalert'),array('product_id','product','department','pnd_time','time','status','user_id','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));
    $ReturnMessages['bot_chats'] = array('last_id_identifier' => 'bot_chats', 'list' => array_values($chats));
    $chatsList[] = & $ReturnMessages['bot_chats']['list'];
}

if ($pendingTabEnabled == true) {
	
	$additionalFilter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);
	
	if (is_array($Params['user_parameters_unordered']['pendingu']) && !empty($Params['user_parameters_unordered']['pendingu'])) {
	    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['pendingu']);
	    $additionalFilter['filterin']['user_id'] = $Params['user_parameters_unordered']['pendingu'];
	} elseif ($showAllPending == 0) {
		$additionalFilter['filter']['user_id'] = $currentUser->getUserID();
	}
	
	if (is_array($Params['user_parameters_unordered']['pendingd']) && !empty($Params['user_parameters_unordered']['pendingd'])) {
	    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['pendingd']);
	    $additionalFilter['filterin']['dep_id'] = $Params['user_parameters_unordered']['pendingd'];
	}
	
	// User groups filter
	if (is_array($Params['user_parameters_unordered']['pugroups']) && !empty($Params['user_parameters_unordered']['pugroups'])) {
	    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['pugroups']);
        $userIds = erLhcoreClassChat::getUserIDByGroup($Params['user_parameters_unordered']['pugroups']);
	    if (!empty($userIds)) {
	        $additionalFilter['filterin']['user_id'] = isset($additionalFilter['filterin']['user_id']) ? array_merge($additionalFilter['filterin']['user_id'],$userIds) : $userIds;
	    }
	}

	if (is_array($Params['user_parameters_unordered']['pdgroups']) && !empty($Params['user_parameters_unordered']['pdgroups'])) {
	    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['pdgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['pdgroups']);
	    if (!empty($depIds)) {
	        $additionalFilter['filterin']['dep_id'] = isset($additionalFilter['filterin']['dep_id']) ? array_merge($additionalFilter['filterin']['dep_id'],$depIds) : $depIds;
	    }
	}
	
	if (is_array($Params['user_parameters_unordered']['pendingdprod']) && !empty($Params['user_parameters_unordered']['pendingdprod'])) {
	    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['pendingdprod']);
	    $additionalFilter['filterin']['product_id'] = $Params['user_parameters_unordered']['pendingdprod'];
	}

	$limitList = is_numeric($Params['user_parameters_unordered']['limitp']) ? (int)$Params['user_parameters_unordered']['limitp'] : 10;

	$filterAdditionalMainAttr = array();
	if ($Params['user_parameters_unordered']['psort'] == 'asc') {
	    $filterAdditionalMainAttr['sort'] = 'priority DESC, id ASC';
	}

    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.syncadmininterface.pendingchats',array('additional_filter' => & $additionalFilter));

	/**
	 * Pending chats
	 * */
	$pendingChats = erLhcoreClassChat::getPendingChats($limitList, 0, $additionalFilter, $filterAdditionalMainAttr);

    $chatsListAll = $chatsListAll+$pendingChats;

	/**
	 * Get last pending chat
	 * */
	erLhcoreClassChat::prefillGetAttributes($pendingChats,array('user_status_front','status_sub_sub','can_edit_chat','time_created_front','product_name','department_name','wait_time_pending','wait_time_seconds','plain_user_name','aicons'), array('product_id','product','department','pnd_time','time','status','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));
	$ReturnMessages['pending_chats'] = array('list' => array_values($pendingChats), 'last_id_identifier' => 'pending_chat');

	$chatsList[] = & $ReturnMessages['pending_chats']['list'];
}

// Transfered chats
$transferchatsUser = erLhcoreClassTransfer::getTransferChats();

// How many chat's there is for operator assigned. Operators Chats
$operatorsCount = 0;

// What operators has send a messages
$operatorsSend = array();

if (!empty($transferchatsUser)) {    
    foreach ($transferchatsUser as & $transf) {

        if ($transf['status'] == erLhcoreClassModelChat::STATUS_OPERATORS_CHAT) {
            $operatorsCount++;
            $operatorsSend[] = (int)$transf['transfer_user_id'];
        }

    	$transf['time_front'] = date(erLhcoreClassModule::$dateDateHourFormat,$transf['time']);
    }
}

// Transfered chats to departments
$transferchatsDep = erLhcoreClassTransfer::getTransferChats(array('department_transfers' => true));
if (!empty($transferchatsDep)) {
	foreach ($transferchatsDep as & $transf){
		$transf['time_front'] = date(erLhcoreClassModule::$dateDateHourFormat,$transf['time']);
	}
}

$ReturnMessages['transfer_chats'] = array('list' => array_values($transferchatsUser),'last_id_identifier' => 'transfer_chat');
$ReturnMessages['transfer_dep_chats'] = array('list' => array_values($transferchatsDep),'last_id_identifier' => 'transfer_chat_dep');

if ($canListOnlineUsers == true || $canListOnlineUsersAll == true) {
    
    $filter = array();
    
    $depIds = array();
    
    if (is_array($Params['user_parameters_unordered']['odpgroups']) && !empty($Params['user_parameters_unordered']['odpgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['odpgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['odpgroups']);
    }
    
    if (is_array($Params['user_parameters_unordered']['operatord']) && !empty($Params['user_parameters_unordered']['operatord'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['operatord']);
        $depIds = array_merge($depIds, $Params['user_parameters_unordered']['operatord']);
    }
    
    if (!empty($depIds)) {
        $filter['customfilter'][] = '(dep_id = 0 OR dep_id IN ('.implode(",", $depIds).'))';
    }
   
    $validSort = array(
        'onl_dsc' => 'hide_online DESC, active_chats DESC',
        'onl_asc' => 'hide_online ASC, active_chats DESC',
        'ac_dsc' => 'active_chats DESC, hide_online ASC',
        'ac_asc' => 'active_chats ASC, hide_online ASC',
        'rac_asc' => '((active_chats + pending_chats) - inactive_chats) ASC, hide_online ASC',
        'rac_dsc' => '((active_chats + pending_chats) - inactive_chats) DESC, hide_online ASC',
    );

    if (key_exists($Params['user_parameters_unordered']['onop'], $validSort)) {
        $filter['sort'] = $validSort[$Params['user_parameters_unordered']['onop']];
    }
    
	$onlineOperators = erLhcoreClassModelUserDep::getOnlineOperators($currentUser,$canListOnlineUsersAll,$filter,is_numeric($Params['user_parameters_unordered']['limito']) ? (int)$Params['user_parameters_unordered']['limito'] : 10,$onlineTimeout);
	
	erLhcoreClassChat::prefillGetAttributes($onlineOperators,array('lastactivity_ago','max_chats','offline_since','user_id','id','name_official','pending_chats','inactive_chats','active_chats','departments_names','hide_online','avatar'),array(),array('filter_function' => true, 'remove_all' => true));

	$currentOp = isset($onlineOperators[$userData->id]) ? $onlineOperators[$userData->id] : null;

    $operatorsCountOnline = 0;

	foreach ($onlineOperators as $onlineOp) {
	    if ($userData->id == $onlineOp->user_id) {
            $currentOp = $onlineOp;
        }

        if ($onlineOp->hide_online == 0) {
            $operatorsCountOnline++;
        }
    }

	$ReturnMessages['online_op'] = array('list' => array_values($onlineOperators), 'op_on' => $operatorsCountOnline, 'op_cc' => $operatorsCount, 'op_sn' => $operatorsSend);
}

if ($unreadTabEnabled == true && is_array($Params['user_parameters_unordered']['w']) && in_array($mapsWidgets['unread_chats'],$Params['user_parameters_unordered']['w'])) {

    $filter = array('ignore_fields' => erLhcoreClassChat::$chatListIgnoreField);

    $limitList = is_numeric($Params['user_parameters_unordered']['limitu']) ? (int)$Params['user_parameters_unordered']['limitu'] : 10;

    if (is_array($Params['user_parameters_unordered']['unreadd']) && !empty($Params['user_parameters_unordered']['unreadd'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['unreadd']);
        $filter['filterin']['dep_id'] = $Params['user_parameters_unordered']['unreadd'];
    }

    if (is_array($Params['user_parameters_unordered']['unreaddprod']) && !empty($Params['user_parameters_unordered']['unreaddprod'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['unreaddprod']);
        $filter['filterin']['product_id'] = $Params['user_parameters_unordered']['unreaddprod'];
    }

    if (is_array($Params['user_parameters_unordered']['udgroups']) && !empty($Params['user_parameters_unordered']['udgroups'])) {
        erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['udgroups']);
        $depIds = erLhcoreClassChat::getDepartmentsByDepGroup($Params['user_parameters_unordered']['udgroups']);
        if (!empty($depIds)) {
            $filter['filterin']['dep_id'] = isset($filter['filterin']['dep_id']) ? array_merge($filter['filterin']['dep_id'],$depIds) : $depIds;
        }
    }
    
	// Unread chats
	$unreadChats = erLhcoreClassChat::getUnreadMessagesChats($limitList,0,$filter);

    $chatsListAll = $chatsListAll+$unreadChats;

	erLhcoreClassChat::prefillGetAttributes($unreadChats, array('user_status_front','time_created_front','product_name','department_name','unread_time','plain_user_name'), array('product_id','product','department','time','pnd_time','status','user','additional_data','additional_data_array','chat_variables','chat_variables_array'),array('additional_columns' => $columnsAdditional));
	$ReturnMessages['unread_chats'] = array('last_id_identifier' => 'unread_chat', 'list' => array_values($unreadChats));
	
	$chatsList[] = & $ReturnMessages['unread_chats']['list'];
}

if (!empty($chatsList)) {
    erLhcoreClassChat::cleanForDashboard($chatsList);
}

$my_active_chats = array();

if ($activeTabEnabled == true && isset($Params['user_parameters_unordered']['topen']) && $Params['user_parameters_unordered']['topen'] == 'true') {
    $activeMyChats = erLhcoreClassChat::getActiveChats(10, 0, array('filter' => array('user_id' => $currentUser->getUserID())));

    $chatsListAll = $chatsListAll+$activeMyChats;

    erLhcoreClassChat::prefillGetAttributes($activeMyChats,array('id','nick'),array(),array('remove_all' => true));
    
    $my_active_chats = array_values($activeMyChats);
}

$version = erLhcoreClassUpdate::LHC_RELEASE;

$mainSyncAttributes = [];

erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.syncadmininterface',array('main_attr' => & $mainSyncAttributes, 'lists' => & $ReturnMessages, 'v' => & $version));

$ou = '';
if ($userData->operation_admin != '') {
    $ou = 1;
}

$responseSync = array('v' => $version, 'error' => 'false', 'mac' => $my_active_chats, 'ina' => $userData->inactive_mode, 'a_on' => $userData->always_on, 'ou' => $ou, 'result' => $ReturnMessages, 'ho' => $userData->hide_online, 'im' => $userData->invisible_mode);

if (isset($currentOp) && $currentOp !== null) {
    $responseSync['ho'] = $currentOp->hide_online;
}

if (!empty($chatsForced)) {
     $responseSync['fs'] = $chatsForced;
}

if (!empty($mainSyncAttributes)) {
    $responseSync = $responseSync + $mainSyncAttributes;
}

echo erLhcoreClassChat::safe_json_encode($responseSync);

exit;
?>