<?php

namespace phpcord\guild;

use phpcord\Discord;
use phpcord\http\RestAPIHandler;
use phpcord\user\User;
use phpcord\utils\ArrayUtils;
use function array_map;
use function is_numeric;
use function var_dump;

class GuildMember extends User {
	/** @var array $roles */
	public $roles = [];

	/** @var bool $bot */
	public $bot = false;

	/** @var string $nick */
	public $nick = "";

	/** @var string $joined_at */
	public $joined_at = "";

	/** @var string|null $premium_since */
	public $premium_since = null;
	
	/** @var bool $deafened */
	protected $deafened = false;
	
	/** @var bool $muted */
	protected $muted = false;
	
	/** @var bool $pending */
	protected $pending = false;
	
	/**
	 * GuildMember constructor.
	 *
	 * @param string $guild_id
	 * @param string $id
	 * @param string $username
	 * @param string $discriminator
	 * @param array $roles
	 * @param bool $bot
	 * @param string $nick
	 * @param int $public_flags
	 * @param string|null $avatar
	 * @param string $joined_at
	 * @param string|null $premium_since
	 * @param bool $deafened
	 * @param bool $muted
	 * @param bool $pending
	 */
	public function __construct(string $guild_id, string $id, string $username, string $discriminator, array $roles, bool $bot = false, string $nick = "", int $public_flags = 0, ?string $avatar = null, string $joined_at = "", ?string $premium_since = null, bool $deafened  = false, bool $muted = false, bool $pending = false) {
		parent::__construct($guild_id, $id, $username, $discriminator, $public_flags, $avatar);
		$this->roles = $roles;
		$this->bot = $bot;
		$this->nick = $nick;
		$this->joined_at = $joined_at;
		$this->premium_since = $premium_since;
		$this->deafened = $deafened;
		$this->muted = $muted;
		$this->pending = $pending;
	}
	
	/**
	 * Returns whether this member is a human or not
	 *
	 * @api
	 *
	 * @return bool
	 */
	public function isHuman(): bool {
		return !$this->bot;
	}
	
	/**
	 * Returns whether this is a bot or not, opposite to @see isHuman()
	 *
	 * @api
	 *
	 * @return bool
	 */
	public function isBot(): bool {
		return $this->bot;
	}
	
	/**
	 * Returns the member ONLY as user
	 *
	 * @api
	 *
	 * @return User
	 */
	public function asUser(): User {
		return new User($this->guild_id, $this->id, $this->username, $this->discriminator, $this->public_flags, $this->avatar);
	}

	/**
	 * Returns a list with all roles (formed as id) of the member
	 *
	 * @api
	 *
	 * @return string[]
	 */
	public function getRolesIds(): array {
		return $this->roles;
	}
	
	/**
	 * Changes the nick of the given member to the target value
	 * Null will reset the nick
	 * For changing the nick of your bot application, @see Guild::setBotNick()
	 *
	 * @api
	 *
	 * @param string|null $nick
	 *
	 * @return bool
	 */
	public function setNick(?string $nick): bool {
		$this->nick = $nick;
		return !RestAPIHandler::getInstance()->updateMember($this->getGuildId(), $this->getId(), [ "nick" => $nick ])->isFailed();
	}
	
	/**
	 * Mutes a player (by server) in voice channels
	 * -> The given player has to be in a voice channel to perform this action, otherwise Bad request will be returned from gateway!
	 *
	 * @api
	 *
	 * @param bool $muted
	 *
	 * @return bool
	 */
	public function setMuted(bool $muted = true): bool {
		$this->muted = $muted;
		return $this->update(null, $muted);
	}
	
	/**
	 * Deafens a player (by server) in voice channels
	 * -> The given player has to be in a voice channel to perform this action, otherwise Bad request will be returned from gateway!
	 *
	 * @api
	 *
	 * @param bool $deafened
	 *
	 * @return bool
	 */
	public function setDeafened(bool $deafened = true): bool {
		$this->deafened = $deafened;
		return $this->update($deafened);
	}
	
	/**
	 * Moves a player into another voice channel
	 * -> The given player has to be in a voice channel to perform this action, otherwise Bad request will be returned from gateway!
	 *
	 * @api
	 *
	 * @param string $channelId
	 *
	 * @return bool
	 */
	public function moveToChannel(string $channelId): bool {
		return $this->update(null, null, $channelId);
	}
	
	/**
	 * Performs a user-update
	 *
	 * @internal
	 *
	 * @param bool|null $deafened
	 * @param bool|null $muted
	 * @param string|null $channelToMove
	 *
	 * @return bool
	 */
	protected function update(?bool $deafened = null, ?bool $muted = null, ?string $channelToMove = null): bool {
		$array = ArrayUtils::filterNullRecursive([
			"deaf" => $deafened,
			"mute" => $muted,
			"channel_id" => $channelToMove
		]);
		return !RestAPIHandler::getInstance()->updateMember($this->getGuildId(), $this->getId(), $array)->isFailed();
	}
	

	/**
	 * Returns a list with all roles (formed as GuildRole instance) of the member
	 *
	 * @api
	 *
	 * @return GuildRole[]
	 */
	public function getRoles(): array {
		$guildId = $this->getGuildId();
		return array_filter(array_map(function($key) use ($guildId) {
			return Discord::$lastInstance->getClient()->getGuild($guildId)->getRole($key);
		}, $this->roles), function($key) {
			return !is_null($key);
		});
	}
	
	/**
	 * Returns whether the member has a permission or not
	 * Uses hasPermission of the roles
	 *
	 * @api
	 *
	 * @param int|string $permission
	 *
	 * @return bool
	 */
	public function hasPermission($permission): bool {
		if (Discord::getInstance()->getClient()->getGuild($this->guild_id)->isOwner($this)) return true;
		foreach ($this->getRoles() as $role) {
			if ($role->hasPermission($permission)) return true;
		}
		return false;
	}
	
	/**
	 * Returns the guild instance of member's guild
	 *
	 * @api
	 *
	 * @return Guild|null
	 */
	public function getGuild(): ?Guild {
		return Discord::getInstance()->getClient()->getGuild($this->getGuildId());
	}
	
	/**
	 * Returns whether a user has a role or not
	 *
	 * @api
	 *
	 * @param GuildRole|string $role
	 *
	 * @return bool
	 */
	public function hasRole($role): bool {
		if ($role instanceof GuildRole) $role = $role->getId();
		return isset($this->roles[$role]);
	}
	
	/**
	 * Adds a role to a member if it doesn't exist already
	 *
	 * @api
	 *
	 * @param GuildRole|string $role
	 *
	 * @return bool
	 */
	public function addRole($role): bool {
		if ($role instanceof GuildRole) $role = $role->getId();
		if ($this->hasRole($role)) return false;
		$this->roles[] = $role;
		return !RestAPIHandler::getInstance()->addRoleToUser($this->getGuildId(), $this->getId(), $role)->isFailed();
	}
	
	/**
	 * Removes a role from a member if not in the list
	 *
	 * @api
	 *
	 * @param $role
	 *
	 * @return bool
	 */
	public function removeRole($role): bool {
		if ($role instanceof GuildRole) $role = $role->getId();
		if (!$this->hasRole($role)) return false;
		if (isset($this->roles[$role])) unset($this->roles[$role]);
		return !RestAPIHandler::getInstance()->removeRoleFromUser($this->getGuildId(), $this->getId(), $role)->isFailed();
	}
	
}