<?php

$tpl = erLhcoreClassTemplate::getInstance( 'lhchat/onlineusers.tpl.php');

if (is_numeric($Params['user_parameters_unordered']['clear_list']) && $Params['user_parameters_unordered']['clear_list'] == 1) {

	if (!$currentUser->validateCSFRToken($Params['user_parameters_unordered']['csfr'])) {
		die('Invalid CSRF Token');
		exit;
	}

    erLhcoreClassModelChatOnlineUser::cleanAllRecords();
    erLhcoreClassModule::redirect('chat/onlineusers');
    exit;
}

if (is_numeric($Params['user_parameters_unordered']['deletevisitor']) && $Params['user_parameters_unordered']['deletevisitor'] > 0) {
	
	if (!$currentUser->validateCSFRToken($Params['user_parameters_unordered']['csfr'])) {
		die('Invalid CSRF Token');
		exit;
	}
	
	try {
		$visitor = erLhcoreClassModelChatOnlineUser::fetch($Params['user_parameters_unordered']['deletevisitor']);
		$visitor->removeThis();
	} catch (Exception $e) {
        print_r($e);
        exit;
	}

    erLhcoreClassModule::redirect('chat/onlineusers');
    exit;
}

$is_ajax = isset($Params['user_parameters_unordered']['method']) && $Params['user_parameters_unordered']['method'] == 'ajax';
$timeout = isset($Params['user_parameters_unordered']['timeout']) && is_numeric($Params['user_parameters_unordered']['timeout']) ? (int)$Params['user_parameters_unordered']['timeout'] : 30;
$maxrows = isset($Params['user_parameters_unordered']['maxrows']) && is_numeric($Params['user_parameters_unordered']['maxrows']) ? (int)$Params['user_parameters_unordered']['maxrows'] : 50;

$filter = array('offset' => 0, 'limit' => $maxrows, 'sort' => 'last_visit DESC','filtergt' => array('last_visit' => (time()-$timeout)));
$department = isset($Params['user_parameters_unordered']['department']) && is_array($Params['user_parameters_unordered']['department']) && !empty($Params['user_parameters_unordered']['department']) ? $Params['user_parameters_unordered']['department'] : false;
if ($department !== false) {
	$filter['filterin']['`lh_chat_online_user`.`dep_id`'] = $department;
}

$departmentGroups = isset($Params['user_parameters_unordered']['department_dpgroups']) && is_array($Params['user_parameters_unordered']['department_dpgroups']) && !empty($Params['user_parameters_unordered']['department_dpgroups']) ? $Params['user_parameters_unordered']['department_dpgroups'] : false;
if ($departmentGroups !== false) {
    erLhcoreClassChat::validateFilterIn($Params['user_parameters_unordered']['department_dpgroups']);
    $db = ezcDbInstance::get();
    $stmt = $db->prepare('SELECT dep_id FROM lh_departament_group_member WHERE dep_group_id IN (' . implode(',',$Params['user_parameters_unordered']['department_dpgroups']) . ')');
    $stmt->execute();
    $depIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($depIds)) {
        if (isset($filter['filterin']['`lh_chat_online_user`.`dep_id`'])){
            $filter['filterin']['`lh_chat_online_user`.`dep_id`'] = array_merge($filter['filterin']['`lh_chat_online_user`.`dep_id`'],$depIds);
        } else {
            $filter['filterin']['`lh_chat_online_user`.`dep_id`'] = $depIds;
        }
    }
}

$country = isset($Params['user_parameters_unordered']['country']) && $Params['user_parameters_unordered']['country'] != '' ? (string)$Params['user_parameters_unordered']['country'] : false;
if ($country !== false && $country != 'none') {
    $filter['filter']['user_country_code'] = $country;
}

$timeonsite = isset($Params['user_parameters_unordered']['timeonsite']) && $Params['user_parameters_unordered']['timeonsite'] != '' ? (string)rawurldecode($Params['user_parameters_unordered']['timeonsite']) : false;

if ($timeonsite !== false && $timeonsite != '' && $timeonsite != 'none') {
    if (strpos($timeonsite,'+') === 0) {
        $filter['filtergt']['time_on_site'] = str_replace('+','',$timeonsite);
    } else {
        $filter['filterlt']['time_on_site'] = str_replace(array('+','-'),'',$timeonsite);
    }
}


/**
 * Append user departments filter
 * */
$departmentParams = array();
$userDepartments = erLhcoreClassUserDep::parseUserDepartmetnsForFilter($currentUser->getUserID(), $currentUser->cache_version);
if ($userDepartments !== true){
	$departmentParams['filterin']['id'] = $userDepartments;	
	if (!$currentUser->hasAccessTo('lhchat','sees_all_online_visitors')) {
		$filter['filterin']['dep_id'] = $userDepartments;
	}
}

if ($is_ajax == true) {
    $columnsAdditional = erLhAbstractModelChatColumn::getList(array('ignore_fields' => array('position','conditions','column_name','column_name','column_identifier','enabled'), 'sort' => false, 'filter' => array('enabled' => 1)));

    $onlineAttributeFilter = [
        'attrf_key_1' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_key_1',''),
        'attrf_val_1' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_val_1',''),
        'attrf_key_2' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_key_2',''),
        'attrf_val_2' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_val_2',''),
        'attrf_key_3' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_key_3',''),
        'attrf_val_3' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_val_3',''),
        'attrf_key_4' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_key_4',''),
        'attrf_val_4' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_val_4',''),
        'attrf_key_5' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_key_5',''),
        'attrf_val_5' => (string)erLhcoreClassModelUserSetting::getSetting('oattrf_val_5','')
    ];


    $db = ezcDbInstance::get();

    for ($i = 1; $i <= 5; $i++) {
        if (
            isset($onlineAttributeFilter['attrf_key_' . $i]) &&
            $onlineAttributeFilter['attrf_key_' . $i] != '' &&
            isset($onlineAttributeFilter['attrf_val_' . $i]) &&
            $onlineAttributeFilter['attrf_val_' . $i] != ''
        ) {
            $values = explode('||',$onlineAttributeFilter['attrf_val_' . $i]);
            $valuesFilter = [];
            foreach ($values as $val) {
                $valuesFilter[] = '( JSON_CONTAINS(`lh_chat_online_user`.`online_attr_system`, ' . $db->quote('"'.$val.'"') . ', '.$db->quote('$.'.$onlineAttributeFilter['attrf_key_' . $i]).' ) )';
            }
            $filter['customfilter'][] = '('.implode(' OR ',$valuesFilter).')';
        }
    }

    $items = erLhcoreClassModelChatOnlineUser::getList($filter);

	erLhcoreClassChat::$trackActivity = (int)erLhcoreClassModelChatConfig::fetchCache('track_activity')->current_value == 1;
	erLhcoreClassChat::$trackTimeout = (int)erLhcoreClassModelChatConfig::fetchCache('checkstatus_timeout')->current_value;

	// Prefill chat objects so we can determine nick more precisely
    erLhcoreClassChat::prefillObjects($items, array(
        array(
            'chat_id',
            'chat',
            'erLhcoreClassModelChat::getList'
        ),
    ));

    $attributes = array('online_attr_system_array','notes_intro','last_check_time_ago','visitor_tz_time','last_visit_seconds_ago','lastactivity_ago','time_on_site_front','can_view_chat','operator_user_send','operator_user_string','first_visit_front','last_visit_front','online_status','nick');
    $attributes_remove =  array('chat','department','operator_user','notes','online_attr_system','chat_variables_array','additional_data_array','online_attr','dep_id','first_visit','message_seen_ts');

    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.onlineusers_attr',array('attr' => & $attributes,'attr_remove' => & $attributes_remove));

	erLhcoreClassChat::prefillGetAttributes($items,$attributes,$attributes_remove,array('do_not_clean' => true, 'additional_columns' => $columnsAdditional));

	if (isset($_GET['view']) && $_GET['view'] == 'html') {
        $tpl = erLhcoreClassTemplate::getInstance( 'lhchat/onlineusers/items.tpl.php');
        $tpl->set('items',$items);
        echo $tpl->fetch();
    } else {
        header('content-type: application/json; charset=utf-8');
        echo json_encode(array_values($items));
    }
	exit;
}

$tpl->set('departmentParams',$departmentParams);
$tpl->set('tracking_enabled',erLhcoreClassModelChatConfig::fetch('track_online_visitors')->current_value == 1);
$tpl->set('geo_location_data',erLhcoreClassModelChatConfig::fetch('geo_location_data')->data);

$Result['content'] = $tpl->fetch();
$Result['path'] = array(array('title' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/onlineusers','Online visitors')));

erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.onlineusers_path',array('result' => & $Result));

$Result['additional_footer_js'] = '<script src="'.erLhcoreClassDesign::designJS('js/angular.lhc.online.min.js').'"></script>';

?>