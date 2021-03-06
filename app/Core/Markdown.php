<?php

namespace Kanboard\Core;

use Parsedown;
use Pimple\Container;

/**
 * Specific Markdown rules for Kanboard
 *
 * @package core
 * @author  norcnorc
 * @author  Frederic Guillot
 */
class Markdown extends Parsedown
{
    /**
     * Task links generated will use the project token instead
     *
     * @access private
     * @var boolean
     */
    private $isPublicLink = false;

    /**
     * Container
     *
     * @access private
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @access public
     * @param  Container  $container
     * @param  boolean    $isPublicLink
     */
    public function __construct(Container $container, $isPublicLink)
    {
        $this->isPublicLink = $isPublicLink;
        $this->container = $container;
        $this->InlineTypes['#'][] = 'TaskLink';
        $this->InlineTypes['@'][] = 'UserLink';
        $this->inlineMarkerList .= '#@';
    }

    /**
     * Handle Task Links
     *
     * Replace "#123" by a link to the task
     *
     * @access public
     * @param  array  $Excerpt
     * @return array|null
     */
    protected function inlineTaskLink(array $Excerpt)
    {
        if (preg_match('!#(\d+)!i', $Excerpt['text'], $matches)) {
            $link = $this->buildTaskLink($matches[1]);

            if (! empty($link)) {
                return array(
                    'extent' => strlen($matches[0]),
                    'element' => array(
                        'name' => 'a',
                        'text' => $matches[0],
                        'attributes' => array('href' => $link),
                    ),
                );
            }
        }

        return null;
    }

    /**
     * Handle User Mentions
     *
     * Replace "@username" by a link to the user
     *
     * @access public
     * @param  array  $Excerpt
     * @return array|null
     */
    protected function inlineUserLink(array $Excerpt)
    {
        if (! $this->isPublicLink && preg_match('/^@([^\s,!.:?]+)/', $Excerpt['text'], $matches)) {
            $user_id = $this->container['userModel']->getIdByUsername($matches[1]);

            if (! empty($user_id)) {
                $url = $this->container['helper']->url->href('UserViewController', 'profile', array('user_id' => $user_id));

                return array(
                    'extent' => strlen($matches[0]),
                    'element' => array(
                        'name' => 'a',
                        'text' => $matches[0],
                        'attributes' => array('href' => $url, 'class' => 'user-mention-link'),
                    ),
                );
            }
        }

        return null;
    }

    /**
     * Build task link
     *
     * @access private
     * @param  integer $task_id
     * @return string
     */
    private function buildTaskLink($task_id)
    {
        if ($this->isPublicLink) {
            $token = $this->container['memoryCache']->proxy($this->container['taskFinderModel'], 'getProjectToken', $task_id);

            if (! empty($token)) {
                return $this->container['helper']->url->href(
                    'TaskViewController',
                    'readonly',
                    array(
                        'token' => $token,
                        'task_id' => $task_id,
                    )
                );
            }

            return '';
        }

        return $this->container['helper']->url->href(
            'TaskViewController',
            'show',
            array('task_id' => $task_id)
        );
    }
}
