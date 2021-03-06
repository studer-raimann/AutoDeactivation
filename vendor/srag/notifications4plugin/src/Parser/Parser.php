<?php

namespace srag\Notifications4Plugin\AutoDeactivation\Parser;

use srag\Notifications4Plugin\AutoDeactivation\Exception\Notifications4PluginException;

/**
 * Interface Parser
 *
 * @package srag\Notifications4Plugin\AutoDeactivation\Parser
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 */
interface Parser
{

    /**
     * @var string
     *
     * @abstract
     */
    const NAME = "";
    /**
     * @var string
     *
     * @abstract
     */
    const DOC_LINK = "";


    /**
     * @return string
     */
    public function getClass() : string;


    /**
     * @return string
     */
    public function getName() : string;


    /**
     * @return string
     */
    public function getDocLink() : string;


    /**
     * @return array
     */
    public function getOptionsFields() : array;


    /**
     * @param string $text
     * @param array  $placeholders
     * @param array  $options
     *
     * @return string
     *
     * @throws Notifications4PluginException
     */
    public function parse(string $text, array $placeholders = [], array $options = []) : string;
}
