<?php

namespace phpcord\command\slash;

use function array_map;

class SlashCommand {
	/** @var string $name */
	protected $name;
	
	/** @var string $description */
	protected $description;
	
	/** @var SlashCommandOption[] $options */
	protected $options;
	
	/**
	 * SlashCommand constructor.
	 *
	 * @param string $name
	 * @param string $description
	 * @param array $options
	 */
	public function __construct(string $name, string $description, array $options = []) {
		$this->name = $name;
		$this->description = $description;
		$this->options = $options;
	}
	
	/**
	 * Adds an option to the slashcommand
	 *
	 * @api
	 *
	 * @param SlashCommandOption $option
	 */
	public function addOption(SlashCommandOption $option) {
		$this->options[] = $option;
	}
	
	public function encode(): array {
		return [
			"name" => $this->name,
			"description" => $this->description,
			"options" => array_map(function(SlashCommandOption $key) {
				return $key->encode();
			}, $this->options)
		];
	}
}