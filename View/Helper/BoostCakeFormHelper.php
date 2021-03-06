<?php
App::uses('FormHelper', 'View/Helper');
App::uses('Set', 'Utility');

class BoostCakeFormHelper extends FormHelper {

	public $helpers = array('Html' => array(
		'className' => 'BoostCake.BoostCakeHtml'
	));

	protected $_divOptions = array();

	protected $_inputOptions = array();

	protected $_inputType = null;

	protected $_fieldName = null;

	protected $_checkboxOptions = array();

	protected $_defaultOffset = 2;

	protected $_currentOffset;

/**
 * Overwrite FormHelper::create()
 */
	public function create($model = null, $options = array()) {

		// Variable label offset
		$offset = $this->_defaultOffset;
		if (isset($options['labelOffset'])) {
			$offset = $options['labelOffset'];
			unset($options['labelOffset']);
		}
		if ($offset > 12 || $offset < 1) {
			$offset = $this->_defaultOffset;
		}
		$this->_currentOffset = $offset;
		$inputDefaults = array(
			'form-horizonal' => array(
				'label' => array('class' => sprintf('col-md-%d control-label', $offset)),
				'wrapInput' => array('tag' => 'div', 'class' => sprintf('col-md-%d', 12 - $offset)),
				),
			'form-inline' => array(
				'label' => array('class' => 'sr-only'),
				)
			);
		if (is_array($model) && empty($options)) {
			$options = $model;
			$model = null;
		}
		if (isset($options['class'])) {
			if (empty($options['inputDefaults'])) {
				$options['inputDefaults'] = array();
			}
			if ($options['class'] == 'form-horizontal') {
				$options['inputDefaults'] = array_merge($options['inputDefaults'], $inputDefaults['form-horizonal']);
			}
			if ($options['class'] == 'form-inline') {
				$options['inputDefaults'] = array_merge($options['inputDefaults'], $inputDefaults['form-inline']);
			}
		}
		$this->_checkboxOptions = array(
			'wrapInput' => array('tag' => 'div', 'class' => sprintf('col-md-%d col-md-offset-%d', 12 - $offset, $offset)),
			'label' => array('class' => null),
			'class' => null
			);

		return parent::create($model, $options);
	}


/**
 * Overwrite FormHelper::input()
 * Generates a form input element complete with label and wrapper div
 *
 * ### Options
 *
 * See each field type method for more information. Any options that are part of
 * $attributes or $options for the different **type** methods can be included in `$options` for input().i
 * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
 * will be treated as a regular html attribute for the generated input.
 *
 * - `type` - Force the type of widget you want. e.g. `type => 'select'`
 * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
 * - `div` - Either `false` to disable the div, or an array of options for the div.
 *	See HtmlHelper::div() for more options.
 * - `options` - For widgets that take options e.g. radio, select.
 * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting (field
 *    error and error messages).
 * - `errorMessage` - Boolean to control rendering error messages (field error will still occur).
 * - `empty` - String or boolean to enable empty select box options.
 * - `before` - Content to place before the label + input.
 * - `after` - Content to place after the label + input.
 * - `between` - Content to place between the label + input.
 * - `format` - Format template for element order. Any element that is not in the array, will not be in the output.
 *	- Default input format order: array('before', 'label', 'between', 'input', 'after', 'error')
 *	- Default checkbox format order: array('before', 'input', 'between', 'label', 'after', 'error')
 *	- Hidden input will not be formatted
 *	- Radio buttons cannot have the order of input and label elements controlled with these settings.
 *
 * Added options
 * - `wrapInput` - Either `false` to disable the div wrapping input, or an array of options for the div.
 *	See HtmlHelper::div() for more options.
 * - `checkboxDiv` - Wrap input checkbox tag's class.
 * - `beforeInput` - Content to place before the input.
 * - `afterInput` - Content to place after the input.
 * - `errorClass` - Wrap input tag's error message class.
 *
 * @param string $fieldName This should be "Modelname.fieldname"
 * @param array $options Each type of input takes different options.
 * @return string Completed form widget.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#creating-form-elements
 */
	public function input($fieldName, $options = array()) {
		$this->_fieldName = $fieldName;

		$default = array(
			'error' => array(
				'attributes' => array(
					'wrap' => 'span',
					'class' => 'help-block text-danger'
				)
			),
			'errorClass' => 'has-error error',
			'div' => array('class' => 'form-group'),
			'class' => 'form-control',
			'wrapInput' => '',
			'beforeInput' => '',
			'afterInput' => '',
			'checkboxDiv' => 'checkbox',
			);

		// Format the label correctly
		if (!empty($options['label']) and !is_array($options['label'])) {
			$options['label'] = array('text' => $options['label']);
		}

		// Tweaks for checkbox - must pass type
		if (isset($options['type']) && $options['type'] == 'checkbox') {
			$options = Hash::merge($this->_checkboxOptions, $options);
		}

		$options = Hash::merge(
			$default,
			$this->_inputDefaults,
			$options
		);

		$this->_inputOptions = $options;

		$options['error'] = false;
		if (isset($options['wrapInput'])) {
			unset($options['wrapInput']);
		}
		if (isset($options['checkboxDiv'])) {
			unset($options['checkboxDiv']);
		}
		if (isset($options['beforeInput'])) {
			unset($options['beforeInput']);
		}
		if (isset($options['afterInput'])) {
			unset($options['afterInput']);
		}
		if (isset($options['errorClass'])) {
			unset($options['errorClass']);
		}

		$inputDefaults = $this->_inputDefaults;
		$this->_inputDefaults = array();

		$html = parent::input($fieldName, $options);

		$this->_inputDefaults = $inputDefaults;

		if ($this->_inputType === 'checkbox') {
			if (isset($options['before'])) {
				$html = str_replace($options['before'], '%before%', $html);
			}
			$regex = '/(<label.*?>)(.*?<\/label>)/';
			if (preg_match($regex, $html, $label)) {
				$label = str_replace('$', '\$', $label);
				$html = preg_replace($regex, '', $html);
				$html = preg_replace(
					'/(<input type="checkbox".*?>)/',
					$label[1] . '$1 ' . $label[2],
					$html
				);
			}
			if (isset($options['before'])) {
				$html = str_replace('%before%', $options['before'], $html);
			}
		}

		return $html;
	}

/**
 * Overwrite FormHelper::_divOptions()
 * Generate inner and outer div options
 * Generate div options for input
 *
 * @param array $options
 * @return array
 */
	protected function _divOptions($options) {
		$this->_inputType = $options['type'];

		if ($this->_inputType === 'checkbox') {
			$divOptions = array(
				'type' => $options['type'],
				'div' => sprintf('col-md-offset-%d col-md-%d', $this->_currentOffset, 12 - $this->_currentOffset)
			);
		} else {
			$divOptions = array(
				'type' => $options['type'],
				'div' => $this->_inputOptions['wrapInput']
			);
		}
		$this->_divOptions = parent::_divOptions($divOptions);

		$default = array('div' => array('class' => null));
		$options = Hash::merge($default, $options);
		$divOptions = parent::_divOptions($options);
		if ($this->tagIsInvalid() !== false) {
			$divOptions = $this->addClass($divOptions, $this->_inputOptions['errorClass']);
		}
		return $divOptions;
	}

	protected function _inputLabel($fieldName, $label, $options) {
		if ($this->_inputType === 'checkbox') {
			//unset($label['class']);
		}
		return parent::_inputLabel($fieldName, $label, $options);
	}

/**
 * Overwrite FormHelper::_getInput()
 * Wrap `<div>` input element
 * Generates an input element
 *
 * @param type $args
 * @return type
 */
	protected function _getInput($args) {
		$input = parent::_getInput($args);
		if ($this->_inputType === 'checkbox' && $this->_inputOptions['checkboxDiv'] !== false) {
			$input = $this->Html->div($this->_inputOptions['checkboxDiv'], $input);
		}

		$beforeInput = $this->_inputOptions['beforeInput'];
		$afterInput = $this->_inputOptions['afterInput'];

		$error = null;
		$errorOptions = $this->_extractOption('error', $this->_inputOptions, null);
		$errorMessage = $this->_extractOption('errorMessage', $this->_inputOptions, true);
		if ($this->_inputType !== 'hidden' && $errorOptions !== false) {
			$errMsg = $this->error($this->_fieldName, $errorOptions);
			if ($errMsg && $errorMessage) {
				$error = $errMsg;
			}
		}

		$html = $beforeInput . $input . $error . $afterInput;

		if ($this->_divOptions) {
			$tag = $this->_divOptions['tag'];
			unset($this->_divOptions['tag']);
			$html = $this->Html->tag($tag, $html, $this->_divOptions);
		}

		return $html;
	}

/**
 * Overwrite FormHelper::_selectOptions()
 * If $attributes['style'] is `<input type="checkbox">` then replace `<label>` position
 * Returns an array of formatted OPTION/OPTGROUP elements
 *
 * @param array $elements
 * @param array $parents
 * @param boolean $showParents
 * @param array $attributes
 * @return array
 */
	protected function _selectOptions($elements = array(), $parents = array(), $showParents = null, $attributes = array()) {
		$selectOptions = parent::_selectOptions($elements, $parents, $showParents, $attributes);

		if ($attributes['style'] === 'checkbox') {
			foreach ($selectOptions as $key => $option) {
				$option = preg_replace('/<div.*?>/', '', $option);
				$option = preg_replace('/<\/div>/', '', $option);
				if (preg_match('/>(<label.*?>)/', $option, $match)) {
					$class = $attributes['class'];
					if (preg_match('/.* class="(.*)".*/', $match[1], $classMatch)) {
						$class = $classMatch[1] . ' ' . $attributes['class'];
						$match[1] = str_replace(' class="' . $classMatch[1] . '"', '', $match[1]);
					}
					$option = $match[1] . preg_replace('/<label.*?>/', ' ', $option);
					$option = preg_replace('/(<label.*?)(>)/', '$1 class="' . $class . '"$2', $option);
				}
				$selectOptions[$key] = $option;
			}
		}

		return $selectOptions;
	}

	protected function _selectOptions_OLD($elements = array(), $parents = array(), $showParents = null, $attributes = array()) {
		$selectOptions = parent::_selectOptions($elements, $parents, $showParents, $attributes);

		if ($attributes['style'] === 'checkbox') {
			foreach ($selectOptions as $key => $option) {
				$option = preg_replace('/<div.*?>/', '', $option);
				$option = preg_replace('/<\/div>/', '', $option);
				if (preg_match('/>(<label.*?>)/', $option, $match)) {
					$option = $match[1] . preg_replace('/<label.*?>/', ' ', $option);
					if (isset($attributes['class'])) {
						$option = preg_replace('/(<label.*?)(>)/', '$1 class="' . $attributes['class'] . '"$2', $option);
					}
				}
				$selectOptions[$key] = $option;
			}
		}

		return $selectOptions;
	}

/**
 * Creates an HTML link, but access the url using the method you specify (defaults to POST).
 * Requires javascript to be enabled in browser.
 *
 * This method creates a `<form>` element. So do not use this method inside an existing form.
 * Instead you should add a submit button using FormHelper::submit()
 *
 * ### Options:
 *
 * - `data` - Array with key/value to pass in input hidden
 * - `method` - Request method to use. Set to 'delete' to simulate HTTP/1.1 DELETE request. Defaults to 'post'.
 * - `confirm` - Can be used instead of $confirmMessage.
 * - Other options is the same of HtmlHelper::link() method.
 * - The option `onclick` will be replaced.
 * - `block` - For nested form. use View::fetch() output form.
 *
 * @param string $title The content to be wrapped by <a> tags.
 * @param string|array $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
 * @param array $options Array of HTML attributes.
 * @param bool|string $confirmMessage JavaScript confirmation message.
 * @return string An `<a />` element.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/form.html#FormHelper::postLink
 */
	public function postLink($title, $url = null, $options = array(), $confirmMessage = false) {
		$block = false;
		if (!empty($options['block'])) {
			$block = $options['block'];
			unset($options['block']);
		}

		$fields = $this->fields;
		$this->fields = array();

		$out = parent::postLink($title, $url, $options, $confirmMessage);

		$this->fields = $fields;

		if ($block) {
			$regex = '/<form.*?>.*?<\/form>/';
			if (preg_match($regex, $out, $match)) {
				$this->_View->append($block, $match[0]);
				$out = preg_replace($regex, '', $out);
			}
		}

		return $out;
	}

/**
 * navBar method
 *
 * Creates a HTML navbar for bootstrap
 */
	public function navBar($menus) {

		$html = null;
		foreach ($menus as $menu) {
			if (isset($menu['role']) and !AuthComponent::user($menu['role'])) {
				continue;
			}
			$html .= '<li class="dropdown">';
			$html .= $this->Html->link(sprintf('<span class="glyphicon glyphicon-%s"></span> %s <b class="caret"></b>', $menu['icon'], $menu['title']), '#', array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown', 'escape' => false));
			if (isset($menu['children'])) {
				$html .= '<ul class="dropdown-menu">';
				foreach ($menu['children'] as $child) {
					if (isset($child['separator']) and $child['separator']) {
						$html .= '<li role="presentation" class="divider"></li>';
						continue;
					}
					$tag = '<li>%s</li>';
					if (Router::url($child['url']) === $this->params->here) {
						$tag = '<li class="active">%s</li>';
					}
					$html .= sprintf($tag, $this->Html->link($child['title'], $child['url']));
				}
				$html .= '</ul>';
			}
			$html .= '</li>';
		}
	}

}
