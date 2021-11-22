<?php
// Copyright 2020 Webstack. All rights reserved.
// 본 소프트웨어는 웹스택이 개발/운용하는 웹스택의 재산으로, 허가없이 무단 이용할 수 없습니다.
// 무단 복제 또는 배포 등의 행위는 관련 법규에 의하여 금지되어 있으며, 위반시 민/형사상의 처벌을 받을 수 있습니다.
// 관련 문의는 웹사이트<https://webstack.me/> 또는 이메일<admin@webstack.me> 로 부탁드립니다.

if(!defined('__XE__'))
{
    exit();
}

// 라이프 사이클 확인
if($called_position != 'before_module_proc')
{
    return;
}

// 애드온 함수 모음 불러오기
require_once(__DIR__ . '/functions.php');

if(AddonFunction::getAddonSession('signed') === true)
{
	$_SESSION['XE_VALIDATOR_MESSAGE'] = '';
	$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = 'success';

	Context::set('XE_VALIDATOR_MESSAGE', '');
	Context::set('XE_VALIDATOR_MESSAGE_TYPE', 'success');

	AddonFunction::setAddonSession('signed', false);
}

// 애드온 설정 기본값 지정
$addon_info = AddonFunction::setDefaultAddonInfo($addon_info, [
    'password' => null,

    'condition_ip' => '',
    'condition_admin' => 'Y',
    'condition_group' => ''
]);

// password 값이 지정되지 않은 경우 애드온 종료
if($addon_info->password === null)
{
    return;
}

// 해시값 소문자로 변경
$addon_info->password = strtolower($addon_info->password);

// IP 대역 확인
if($addon_info->condition_ip != '')
{
    $ip_matched = AddonFunction::getAddonSession('ip_matched');
    if($ip_matched === null)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ip_list = explode("\n", $addon_info->condition_ip);

        foreach($ip_list as $range)
        {
            $count = count(explode('.', $range));
            if($count < 4)
            {
                $range .= str_repeat('.*', 4 - $count);
            }
            $range = str_replace(array('.', '*'), array('\\.', '\\d+'), trim($range));

            if(preg_match("/^$range$/", $ip) ? true : false)
            {
                $ip_matched = true;
                break;
            }
        }

        AddonFunction::setAddonSession('ip_matched', $ip_matched);
    }

    if(!$ip_matched)
    {
        return;
    }
}

// 트리거 사용을 위한 애드온 글로벌라이징
AddonFunction::setGlobalAddonInfo($addon_info);

// 로그인 트리거 등록
AddonFunction::addTriggerFunction('member.doLogin', 'before', function($args){
    $addon_info = AddonFunction::getGlobalAddonInfo();

    if(sha1($args->password) == $addon_info->password)
    {
        $oMemberModel = getModel('member');
        $member_config = $oMemberModel->getMemberConfig();

        $user_id = $args->user_id;

        // 이메일 로그인
        if((!$config->identifiers || in_array('email_address', $config->identifiers)) && strpos($user_id, '@') !== false)
        {
            $member_info = $oMemberModel->getMemberInfoByEmailAddress($user_id);
            if(!$user_id || strtolower($member_info->email_address) !== strtolower($user_id))
            {
                return;
            }
        }

        // 전화번호 로그인
        elseif($config->identifiers && in_array('phone_number', $config->identifiers) && strpos($user_id, '@') === false)
        {
            if(preg_match('/^\+([0-9-]+)\.([0-9.-]+)$/', $user_id, $matches))
			{
				$user_id = $matches[2];
				$phone_country = $matches[1];
				if($config->phone_number_hide_country === 'Y')
				{
					$phone_country = $config->phone_number_default_country;
				}
			}
			elseif($config->phone_number_default_country)
			{
				$phone_country = $config->phone_number_default_country;
            }
            else
            {
                return;
            }

            // 전화번호 로그인은 라이믹스만 가능하므로 라이믹스 함수 사용이 안전함
            if($phone_country && !preg_match('/^[A-Z]{3}$/', $phone_country))
			{
				$phone_country = Rhymix\Framework\i18n::getCountryCodeByCallingCode($phone_country);
			}
			
			$user_id = preg_replace('/[^0-9]/', '', $user_id);
            $member_info = $oMemberModel->getMemberInfoByPhoneNumber($user_id, $phone_country);
            
            if(!$user_id || strtolower($member_info->phone_number) !== strtolower($user_id))
            {
                return;
            }
        }

        // 아이디 로그인
        elseif(!$config->identifiers || in_array('user_id', $config->identifiers))
		{
			$member_info = $oMemberModel->getMemberInfoByUserID($user_id);
			if(!$user_id || strtolower($member_info->user_id) !== strtolower($user_id))
			{
				return;
			}
        }
        
        // 관리자 계정 예외 처리시
        if($addon_info->condition_admin == 'N')
        {
            if($member_info->is_admin == 'Y')
            {
                return;
            }
        }

        // 특정 그룹 예외 처리시
        if($addon_info->condition_group != '')
        {
            $group_list = explode(',', $addon_info->condition_group);
            foreach($group_list as $group)
            {
                if(in_array(trim($group), $member_info->group_list))
                {
                    return;
                }
            }
        }
        
		if(AddonFunction::isRhymix())
		{
	        Rhymix\Framework\Session::login($member_info->member_srl);
			AddonFunction::setAddonSession('signed', true);
		}
		else
		{
			$oMemberController = getController('member');
			$oMemberController->doLogin($user_id, '', $args->keep_sign_in);
			AddonFunction::setAddonSession('signed', true);
		}

        return true;
    }
});
