<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\User;

class UserModel {
	public function getUserById(int $id, $domain = null)
	{

		if ($domain === null) {
			$domainId = DOMAIN_ID;
		} else {
			$domainId = Router::getDomainId($domain);
		}

		$user = DB::table('users')
			->join('user_domain_user_group', 'users.id', '=', 'user_domain_user_group.user_id')
			->join('user_groups', 'user_domain_user_group.user_group_id', '=', 'user_groups.id')
			->join('domains', 'user_domain_user_group.domain_id', '=', 'domains.id')
			->select(
				'users.id as id',
				'users.username',
				'users.first_name as firstName',
				'users.last_name as lastName',
				'users.email',
				'users.phone',
				'users.address',
				'users.default_language_id as defaultLanguageId',
				'users.password',
				'user_groups.name as userGroup',
				'user_groups.clearance_level as clearanceLevel',
				'domains.id as domainId'
				)
			->where('users.id', $id)
			->where('user_domain_user_group.domain_id', $domainId)
			->first();

		return $user();
	}

	public function getUserByUsername(string $username, $domain = null)
	{

		if ($domain === null) {
			$domainId = DOMAIN_ID;
		} else {
			$domainId = Router::getDomainId($domain);
		}

		$user = DB::table('users')
			->join('user_domain_user_group', 'users.id', '=', 'user_domain_user_group.user_id')
			->join('user_groups', 'user_domain_user_group.user_group_id', '=', 'user_groups.id')
			->join('domains', 'user_domain_user_group.domain_id', '=', 'domains.id')
			->select(
				'users.id as id',
				'users.username',
				'users.first_name as firstName',
				'users.last_name as lastName',
				'users.email',
				'users.phone',
				'users.address',
				'users.default_language_id as defaultLanguageId',
				'users.password',
				'user_groups.name as userGroup',
				'user_groups.clearance_level as clearanceLevel',
				'domains.id as domainId'
				)
			->where('users.username', $username)
			->where('user_domain_user_group.domain_id', $domainId)
			->first();

		return $user;
	}
}
