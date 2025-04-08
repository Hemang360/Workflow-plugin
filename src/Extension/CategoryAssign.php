<?php

namespace Joomla\Plugin\Workflow\CategoryAssign\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Workflow\WorkflowServiceInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Workflow\WorkflowTransitionEvent;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Workflow CategoryAssign plugin
 *
 * @since  1.0.0
 */
class CategoryAssign extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentBeforeChangeStageDo' => 'onContentBeforeChangeStageDo',
            'onContentPrepareForm' => 'onContentPrepareForm',
        ];
    }

    /**
     * The save event.
     *
     * @param   string  $context     The context
     * @param   object  $table       The item
     * @param   object  $transition  The transition
     * @param   array   $data        The data
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onContentBeforeChangeStageDo($context, $table, $transition, $data)
    {
        // For Joomla 5, we might need to handle a WorkflowTransitionEvent
        if ($context instanceof WorkflowTransitionEvent) {
            $event = $context;
            $context = $event->getArgument('extension');
            $table = $event->getArgument('item');
            $transition = $event->getArgument('transition');
            $data = $event->getArgument('data');
        }

        // Only run in com_content
        if ($context != 'com_content.article') {
            return true;
        }

        // Check if the plugin is enabled
        if (!$this->isEnabled($context)) {
            return true;
        }

        // Check if a category is selected for this transition
        if (empty($transition->options->category_id)) {
            return true;
        }

        // Assign the article to the selected category
        $table->catid = $transition->options->category_id;

        return true;
    }

    /**
     * Disable the category selection field when the plugin is active
     *
     * @param   PrepareFormEvent|Form  $eventOrForm  The event or form
     * @param   mixed                  $data         The data
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onContentPrepareForm($eventOrForm, $data = null)
    {
        // Handle both Joomla 4 and Joomla 5 event patterns
        $form = $eventOrForm;
        
        // For Joomla 5, extract the form from the event
        if ($eventOrForm instanceof PrepareFormEvent) {
            $form = $eventOrForm->getForm();
            $data = $eventOrForm->getData();
        }

        // Get the form name
        $context = $form->getName();

        // Check if we are in the article form
        if ($context === 'com_content.article') {
            // Check if the plugin is enabled 
            if (!$this->isEnabled('com_content.article')) {
                return true;
            }

            // Get the category field
            $categoryField = $form->getField('catid');
            if ($categoryField) {
                // Always make it readonly
                $categoryField->readonly = true;
                $categoryField->disabled = true;
                
                // Get current category value
                $catid = null;
                if (is_object($data)) {
                    $catid = $data->catid ?? null;
                } elseif (is_array($data)) {
                    $catid = $data['catid'] ?? null;
                }

                // If no valid category is set, use uncategorised (ID 2)
                if (empty($catid)) {
                    $form->setValue('catid', null, 2);
                }
            }

            return true;
        }

        // For transition form
        if ($context === 'com_workflow.transition') {
            // Load our form file to add the category field
            $formFile = __DIR__ . '/../../forms/transition.xml';
            if (file_exists($formFile)) {
                $form->loadFile($formFile);
            }
            return true;
        }

        return true;
    }

    /**
     * Check if the plugin is enabled for this context
     *
     * @param   string  $context  The context to check
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function isEnabled($context)
    {
        $parts = explode('.', $context);

        // We need at least the extension + view for loading the component workflow service
        if (count($parts) < 2) {
            return false;
        }

        // For Joomla 5, check if workflows are enabled in a different way
        if ($parts[0] === 'com_content') {
            $params = ComponentHelper::getParams('com_content');
            return (bool) $params->get('workflow_enabled', 1);
        }

        return false;
    }
} 