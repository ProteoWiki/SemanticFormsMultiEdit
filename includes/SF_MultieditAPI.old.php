<?php

/**
 * File holding the SFMultiEditAPI class
 *
 * @author Stephan Gambke
 * @file
 * @ingroup SemanticForms
 */

/**
 * The SF_MultiEditAPI class.
 *
 * @ingroup SemanticForms
 */
class SFMultieditAPI extends ApiBase {

	private $mOptions = array();
	private $mIsApiQuery = true;

	/**
	 * Handles multiedit Ajax call from #multiedit parser function and from save
	 * and continue button.
	 *
	 * @param String $optionsString the options/data string
	 * @param String $prefillFromExisting String set to 'true' to retain existing form values (unset by save and continue)
	 * @return String
	 */
	static function handleMultiEdit( $optionsString = null, $prefillFromExisting = 'true' ) {

		global $wgParser, $wgRequest;

		$handler = new self( new ApiMain( $wgRequest, true ), 'sfmultiedit' );
		$handler->isApiQuery( false );
		$options = $handler->setOptionsString( $optionsString );

		// get oktext (or use default)
		if ( array_key_exists( 'ok text', $options ) ) {
			$oktext = $options['ok text'];
		} else {
			$oktext = wfMsg( 'sf_multiedit_success' );
		}

		// get errortext (or use default)
		if ( array_key_exists( 'error text', $options ) ) {
			$errortext = $options['error text'];
		} else {
			$errortext = '$1';
		}

		// process data
		// result will be true or ames[0]n error messagm
		$result = $handler->storeSemanticData( $prefillFromExisting === 'true' );

		// wrap result in ok/error message
		if ( $result === true ) {

			$options = $handler->getOptions();
			$result = wfMsgReplaceArgs( $oktext, array( $options['target'], $options['form'] ) );

		} else {

			$result->setResponseCode( '400 Bad Request' );
			$result = wfMsgReplaceArgs( $errortext, array( $result ) );
		}

		// initialize parser
		$title = Title::newFromText( 'DummyTitle' );

		if ( !StubObject::isRealObject( $wgParser ) ) {
			$wgParser->_unstub();
		}

		$parseroptions = $wgParser->getOptions();

		if ( $parseroptions == null ) {
			$parseroptions = new ParserOptions();
			$wgParser->Options( $parseroptions );
		}

		$parseroptions->enableLimitReport( false );


		$result = new AjaxResponse( $wgParser->parse( $result, $title, $parseroptions )->getText() );
		$result->setContentType( 'text/html' );

		return $result;
	}

	/**
	 * Getter/setter for the ApiQuery flag.
	 *
	 * If this is set, we are in an API query, else we are in an Ajax query.
	 *
	 * @param bool $isApiQuery Optional. The new value
	 * @return The old value
	 */
	function isApiQuery() {
		$ret = $this->mIsApiQuery;

		$params = func_get_args();

		if ( isset( $params[0] ) ) {
			$this->mIsApiQuery = $params[0];
		}
		return $ret;
	}

	/**
	 * Converts an options string into an options array and stores it
	 *
	 * @param string $options
	 * @return the options array
	 */
	function setOptionsString( $options ) {
		return $this->parseDataFromQueryString( $this->mOptions, $options );
	}

	/**
	 * Returns the options array
	 * @return array
	 */
	function getOptions() {
		return $this->mOptions;
	}

	/**
	 * Sets the options array
	 */
	function setOptions( $options ) {
		$this->mOptions = $options;
	}

	/**
	 * Evaluates the parameters, performs the requested API query, and sets up
	 * the result.
	 */
	function execute() {
		$this->mOptions = $_POST + $_GET;
		$this->isApiQuery( true );

		// if this is an Ajax request
		if ( array_key_exists( 'query', $this->mOptions ) ) {
			// if 'query' parameter was used, unpack it
			$this->setOptionsString( $this->mOptions['query'] );
			unset( $this->mOptions['query'] );
		}

		return $this->storeSemanticData();
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * Returns the array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array or false
	 */
	function getAllowedParams() {
		return array(
			'form' => null,
			'target' => null,
			'query' => null
		);
	}

	/**
	 * Returns an array of parameter descriptions.
	 * Don't call this functon directly: use getFinalParamDescription() to
	 * allow hooks to modify descriptions as needed.
	 * @return array or false
	 */
	function getParamDescription() {
		return array(
			'form' => 'The form to use.',
			'target' => 'The target page.',
			'query' => 'The query string.'
		);
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	function getDescription() {
		return <<<END
This module is used to remotely create or edit pages using Semantic Forms.

Add "template-name[field-name]=field-value" to the query string parameter, to set the value for a specific field.
To set values for more than one field use "&", or rather its URL encoded version "%26": "template-name[field-name-1]=field-value-1%26template-name[field-name-2]=field-value-2".
See the first example below.

In addition to the query parameter, any parameter in the URL of the form "template-name[field-name]=field-value" will be treated as part of the query. See the second example.
END;
	}

	/**
	 * Returns usage examples for this module.
	 * @return mixed string or array of strings
	 */
	protected function getExamples() {
		return array(
			'With query parameter:    api.php?action=sfmultiedit&form=form-name&target=page-name&query=template-name[field-name-1]=field-value-1%26template-name[field-name-2]=field-value-2',
			'Without query parameter: api.php?action=sfmultiedit&form=form-name&target=page-name&template-name[field-name-1]=field-value-1&template-name[field-name-2]=field-value-2'
		);
	}

	/**
	 * Returns a string that identifies the version of the class.
	 * Includes the class name, the svn revision, timestamp, and
	 * last author.
	 * @return string
	 */
	function getVersion() {
		return __CLASS__ . ': $Id$';
	}

	/**
	 *	This method will try to store the data in mOptions.
	 *
	 * It will return true on success or an error message on failure.
	 * The used form and target page will be available in mOptions after
	 * execution of the method.
	 *
	 * This method also sets HTTP response headers according to the result.
	 *
	 * @param bool $prefillFromExisting If this is set, existing values in the page will be used to prefill the form.
	 * @return true or an error message
	 */
	public function storeSemanticData( $prefillFromExisting = true ) {

		global $wgOut, $wgRequest;

		// If the wiki is read-only we might as well stop right away
		if ( wfReadOnly ( ) ) {
			return $this->reportError( wfMsg( 'sf_multiedit_readonly', wfReadOnlyReason() ) );
		}

		// ensure 'form' key exists
		if ( !array_key_exists( 'form', $this->mOptions ) ) {
			$this->mOptions['form'] = null;
		}

		// ensure 'target' key exists
		if ( !array_key_exists( 'target', $this->mOptions ) ) {
			$this->mOptions['target'] = null;
		}

		// If we have no target article and no form we might as well stop right away
		if ( !$this->mOptions['target'] && !$this->mOptions['form'] ) {
			return $this->reportError( wfMsg( 'sf_multiedit_notargetspecified' ) );
		}

		// check if form was specified
		if ( !$this->mOptions['form'] ) {

			// If no form was specified, find the default one for
			// this page.
			$title = Title::newFromText( $this->mOptions['target'] );
			$form_names = SFFormLinker::getDefaultFormsForPage( $title );

			// if no form can be found, return
			if ( count( $form_names ) == 0 ) {
				return $this->reportError( wfMsg( 'sf_multiedit_noformfound' ) );
			}

			// if more than one form found, return
			if ( count( $form_names ) > 1 ) {
				return $this->reportError( wfMsg( 'sf_multiedit_toomanyformsfound' ) );
			}

			// There should now be exactly one form.
			$this->mOptions['form'] = $form_names[0];
		}

		// Overwrites
		$overwrite = 1;
		if ($this -> mOptions [ 'overwrite' ]) {
                        $overwrite = $this -> mOptions [ 'overwrite' ];
                }

                // Iterates considering end
		$digits = 0;               
 
                $end = $this -> mOptions[ 'end' ];
                $start = $this -> mOptions [ 'start' ];
		if ($this -> mOptions [ 'digits' ]) {
			$digits = $this -> mOptions [ 'digits' ];
		}
		
                if (!is_numeric($end) || !is_numeric($start)) {
                        
                        //Change return -> No numer
                        return 'multiedit-noformfound';
                }
	
		if (!is_numeric($digits)) {$digits = 0;}

                
                if ($end<0 || $start<0) {
                        //Change return -> Avoid too high or too low values
                        return 'multiedit-noformfound';
                }

                if (($end-$start)>100) {
                        //Restrict too many pages
                        return 'multiedit-noformfound';
                }
		
		$base = $this -> mOptions[ 'target' ];
		$checkbase = $this -> mOptions [ 'checkbase' ];
		
		$matchbase = 0;
		$patternbase = "/".$checkbase."/";
		$matchbase = preg_match ( $patternbase , $base );		

		if ($matchbase == 0) {
			echo "Incorrect base name for samples. Contact webmaster.";
			exit;
		}
	
		$list_pages = array();

		//Iteration
                $iter = $start; //Start point
                while (($iter < $end+1) && $matchbase > 0) {
                
			// we only care for the form's body
			$wgOut->setArticleBodyOnly( true );

			$formedit = new SFFormEdit();
			$data = array();

			$oldRequest = $wgRequest;
			$copy  = $this;

			$iterend = $copy->digitsround($iter, $digits);

			$copy -> mOptions['target'] = $base.$iterend;
			// Put list of samples in array for the mailing
			array_push($list_pages, str_replace(' ', '_', $copy -> mOptions['target']));

			if ($overwrite == 0) {
				if (  checkExistPages( $list_pages ) ) {
	        	                echo "Samples already exist. Contact webmaster.";
		                        exit;
				}	
			}			

			// Get the form definition and target page (if there is one),
			// as specified in the options string, then create the actual
			// HTML form from them, and call that form to modify or create
			// the page.
			if ( $prefillFromExisting ) {
				$wgRequest = new FauxRequest( $copy->mOptions, true );
	
				// get the Semantic Form
				if ( $this->mOptions['target'] ) {
					$formedit->execute( $copy->mOptions['form'] . '/' . $copy->mOptions['target'] );
				} else {
					$formedit->execute( $copy->mOptions['form'] );
				}
	
				// extract its data
				$form = $copy->parseDataFromHTMLFrag( $data, trim( $wgOut->getHTML() ), 'sfForm' );
	
				if ( !$form ) {
					// something went wrong
					$wgRequest = $oldRequest;
	
					return $copy->reportError( wfMsg( 'sf_multiedit_nosemanticform',
							array(
								$copy->mOptions['target'],
								$copy->mOptions['form']) )
					);
				}
			} else {
				$copy->addToArray( $data, "wpSave", "Save" );
			}
			// and modify as specified
			$data = SFUtils::array_merge_recursive_distinct( $data, $this->mOptions );


			////////////////////////////////////////////////////////////////////////
			// Store the modified form
			//$wgOut->clearHTML();
			$wgRequest = new FauxRequest( $data, true );
	
			// get the MW form
			if ( $copy->mOptions['target'] ) {
				$formedit->execute( $copy->mOptions['form'] . '/' . $copy->mOptions['target'], false );
			} else {
				$formedit->execute( $copy->mOptions['form'], false );
			}
	
			$copy->mOptions['form'] = $formedit->mForm;
			$copy->mOptions['target'] = $formedit->mTarget;
	
			$wgRequest = $oldRequest;
	
			if ( $formedit->mError ) {
	
				return $copy->reportError( $formedit->mError );
			} else {
	
				header( "X-Location: " . $wgOut->getRedirect() );
				header( "X-Form: " . $formedit->mForm );
				header( "X-Target: " . $formedit->mTarget );
	
				if ( $copy->isApiQuery() ) {
					$copy->getResult()->addValue( null, 'result',
						array(
							'code' => '200',
							'location' => $wgOut->getRedirect(),
							'form' => $formedit->mForm,
							'target' => $formedit->mTarget
						)
					);
				}
			}
	
		       
		       //Iteration
		       $iter++;

		}
		
		
		// ensure 'someone to mail' exists
		if ( array_key_exists( 'mail', $this->mOptions ) ) {
			
			$origin = "";
			if ( array_key_exists( 'origin', $this->mOptions ) ) {
				
				$origin = $this -> mOptions[ 'origin' ];
			}
			
			$this->mailList($this -> mOptions[ 'mail' ], $list_pages, $origin);
		}
		

		return true;
	}

	private function checkExistPages ( $list_pages ) {

		foreach ($list_pages as $page) {
			if ( Title::newFromText( $page ) ) {
				return true;
			}
		}

		return false;
	}
	
	private function mailList ( $username, $list_pages, $origin ) {
	
		global $wgSemanticTasksDefaultEmail;
		global $wgSemanticTasksDefaultName;
		global $wgSitename;	

		$assigneeobj = User::newfromName($username);
                $to_address = new MailAddress( $assigneeobj->getEmail(), $assigneeobj->getName() );
	
		$subject = wfMsgExt('samples-subject', 'parsemag', $wgSitename, $origin, $assigneeobj->getName() );
		
		$from_address = new MailAddress( $wgSemanticTasksDefaultEmail, $wgSemanticTasksDefaultName );
		
		$origin_url = Title::newFromText( $origin )->escapeFullURL();
		
		$list_samples = implode("\r\n", $list_pages);
		$body = wfMsgExt('samples-body', 'parsemag', $assigneeobj->getName(), $origin, $origin_url, $list_samples );
		
		$user_mailer = new UserMailer();
                $user_mailer->send( $to_address, $from_address, $subject, $body );


	}

	private function digitsround ( $number, $digits ) {

		$numdig = strlen($number);
		$iter = $digits - $numdig;
		
		$repeat = "";

		if ($iter > 0) {
			$inum = 0;
			while ($inum < $iter) {
				$repeat = "0".$repeat;
				$inum++;
			}		
		}

		return($repeat.$number);
	}


	private function parseDataFromHTMLFrag( &$data, $html, $formID ) {
		$doc = new DOMDocument();
		@$doc->loadHTML(
				'<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body>'
				. $html
				. '</body></html>'
		);

		$form = $doc->getElementById( $formID );

		if ( !$form ) {
			return null;
		}

		// Process input tags
		$inputs = $form->getElementsByTagName( 'input' );

		for ( $i = 0; $i < $inputs->length; $i++ ) {

			$input = $inputs->item( $i );
			$type = $input->getAttribute( 'type' );
			$name = trim( $input->getAttribute( 'name' ) );

			if ( !$name )
				continue;

			if ( $type === '' )
				$type = 'text';

			switch ( $type ) {
				case 'checkbox':
				case 'radio':
					if ( $input->hasAttribute( 'checked' ) ) {
						self::addToArray( $data, $name, $input->getAttribute( 'value' ) );
					}
					break;

				// case 'button':
				case 'hidden':
				case 'image':
				case 'password':
				// case 'reset':
				// case 'submit':
				case 'text':
					self::addToArray( $data, $name, $input->getAttribute( 'value' ) );
					break;

				case 'submit':
					if ( $name == "wpSave" )
						self::addToArray( $data, $name, $input->getAttribute( 'value' ) );
			}
		}

		// Process select tags
		$selects = $form->getElementsByTagName( 'select' );

		for ( $i = 0; $i < $selects->length; $i++ ) {

			$select = $selects->item( $i );
			$name = trim( $select->getAttribute( 'name' ) );

			if ( !$name )
				continue;

			$options = $select->getElementsByTagName( 'option' );

			if ( count( $options ) && ( !$select->hasAttribute( "multiple" ) || $options->item( 0 )->hasAttribute( 'selected' ) ) ) {
				self::addToArray( $data, $name, $options->item( 0 )->getAttribute( 'value' ) );
			}

			for ( $o = 1; $o < $options->length; $o++ ) {
				if ( $options->item( $o )->hasAttribute( 'selected' ) )
					self::addToArray( $data, $name, $options->item( $o )->getAttribute( 'value' ) );
			}
		}

		// Process textarea tags
		$textareas = $form->getElementsByTagName( 'textarea' );

		for ( $i = 0; $i < $textareas->length; $i++ ) {

			$textarea = $textareas->item( $i );
			$name = trim( $textarea->getAttribute( 'name' ) );

			if ( !$name )
				continue;

			self::addToArray( $data, $name, $textarea->textContent );
		}

		return $form;
	}

	/**
	 * Parses data from a query string into the $data array
	 *
	 * @param Array $data
	 * @param String $queryString
	 * @return Array
	 */
	private function parseDataFromQueryString( &$data, $queryString ) {
		$params = explode( '&', $queryString );

		foreach ( $params as $param ) {
			$elements = explode( '=', $param, 2 );

			$key = trim( urldecode( $elements[0] ) );
			$value = count( $elements ) > 1 ? urldecode( $elements[1] ) : null;

			if ( $key == "query" || $key == "query string" ) {
				$this->parseDataFromQueryString( $data, $value );
			} else {
				self::addToArray( $data, $key, $value );
			}
		}

		return $data;
	}

	// This function recursively inserts the value into a tree.
	// $array is root
	// $key identifies path to position in tree.
	// Format: 1stLevelName[2ndLevel][3rdLevel][...], i.e. normal array notation
	// $value: the value to insert
	// $toplevel: if this is a toplevel value.
	public static function addToArray( &$array, $key, $value, $toplevel = true ) {
		$matches = array();

		if ( preg_match( '/^([^\[\]]*)\[([^\[\]]*)\](.*)/', $key, $matches ) ) {

			// for some reason toplevel keys get their spaces encoded by MW.
			// We have to imitate that.
			// FIXME: Are there other cases than spaces?
			if ( $toplevel ) {
				$key = str_replace( ' ', '_', $matches[1] );
			} else {
				$key = $matches[1];
			}

			if ( !array_key_exists( $key, $array ) )
				$array[$key] = array();

			self::addToArray( $array[$key], $matches[2] . $matches[3], $value, false );
		} else {

			if ( $key ) {
				$array[$key] = $value;
			} else {
				array_push( $array, $value );
			}
		}
	}

	/**
	 * Set HTTP error header and add error message to the ApiResult
	 * @param String $msg
	 */
	private function reportError( $msg ) {
		if ( $this->isApiQuery() ) {
			if ( !headers_sent() ) {
				header( 'HTTP/Status: 400 Bad Request' );
			}
			$this->getResult()->addValue( null, 'result', array( 'code' => '400', '*' => $msg ) );
		}
		return $msg;
	}

}
