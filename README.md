# Category Assign Workflow Plugin for Joomla 5

This plugin enhances Joomla 5's workflow system by allowing administrators to automatically assign articles to specific categories when executing workflow transitions.

## Features

- In the transition setup, the admin can select a category
- After executing the transition for an article, the article is assigned to the defined category
- The category selection in the article is disabled when the plugin is active
- Uses the latest plugin structure Joomla 5 offers
- Uses existing Joomla methods

## Installation

1. Download the ZIP file
2. Go to Joomla administrator → System → Install → Extensions
3. Select "Upload Package File" and upload the ZIP file
4. The plugin will be installed automatically

## Usage

1. Go to Content → Workflows
2. Create a new workflow or edit an existing one
3. Add a new transition or edit an existing one
4. In the transition form, you'll see a new "Category" field where you can select a category
5. When this transition is executed, articles will be automatically assigned to the selected category

## Bonus - Handling Custom Fields After Category Change

Here's a code suggestion for handling custom fields after changing the category:

```php
/**
 * Handle custom fields after category change
 * This method would need to be added to the CategoryAssign class
 */
private function handleCustomFields($article, $oldCategoryId, $newCategoryId)
{
    // Get the FieldsModel to access custom fields
    $fieldsModel = Factory::getApplication()->bootComponent('com_fields')
        ->getMVCFactory()->createModel('Fields', 'Administrator');
    
    // Get custom fields for the new category
    $newFields = $fieldsModel->getFields('com_content.article', ['category' => $newCategoryId]);
    
    // Check for required fields in the new category
    $requiredFields = [];
    foreach ($newFields as $field) {
        if ($field->required && empty($field->rawvalue)) {
            $requiredFields[] = $field;
        }
    }
    
    if (!empty($requiredFields)) {
        // Option 1: Auto-populate with default values
        foreach ($requiredFields as $field) {
            // Set default value if available
            if (isset($field->default_value)) {
                $fieldsModel->setFieldValue($field->id, $article->id, $field->default_value);
            }
        }
        
        // Option 2: Create a notification for the administrator
        Factory::getApplication()->enqueueMessage(
            Text::plural(
                'PLG_WORKFLOW_CATEGORYASSIGN_REQUIRED_FIELDS_NOTIFICATION',
                count($requiredFields)
            ),
            'warning'
        );
    }
    
    return true;
}
```

This code suggestion addresses the scenario where changing a category might result in new custom fields that are required. It:

1. Identifies any required custom fields in the new category
2. Auto-populates them with default values where available
3. Notifies the administrator about required fields that need attention

To implement this, you would need to add a call to this method within the `onContentBeforeChangeStageDo` method after changing the category.
