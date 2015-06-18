<?php
/**
 * Parser functions for Semantic Forms MultiEdit, extracted from Semantic Forms.

 * @author Yaron Koren
 * @author Sergey Chernyshev
 * @author Daniel Friesen
 * @author Barry Welch
 * @author Christoph Burgmer
 * @author Stephan Gambke
 * @author MWJames
 * @author Toniher
 */

class SFMEParserFunctions {

	// static variable to guarantee that Javascript for autocompletion
	// only gets added to the page once
	static $num_autocompletion_inputs = 0;

	static function registerFunctions( &$parser ) {
		global $wgOut;

		$parser->setFunctionHook( 'multiedit', array( 'SFMEParserFunctions', 'rendermultiedit' ) );

		return true;
	}

	static function renderMultiEdit( &$parser ) {
		// set defaults
		$formcontent = '';
		$linkString = null;
		$linkType = 'span';
		$summary = null;
		$classString = 'multiedit-trigger';
		$inQueryArr = array();
		$editTime = null;

		// parse parameters
		$params = func_get_args();
		array_shift( $params ); // don't need the parser

		foreach ( $params as $param ) {

			$elements = explode( '=', $param, 2 );

			$key = trim( $elements[ 0 ] );
			$value = ( count( $elements ) > 1 ) ? trim( $elements[ 1 ] ) : '';

			switch ( $key ) {
				case 'link text':
					$linkString = $parser->recursiveTagParse( $value );
					break;
				case 'link type':
					$linkType = $parser->recursiveTagParse( $value );
					break;
				case 'reload':
					$classString .= ' reload';
					break;
				case 'summary':
					$summary = $parser->recursiveTagParse( $value );
					break;
				case 'query string' :

					// Change HTML-encoded ampersands directly to
					// URL-encoded ampersands, so that the string
					// doesn't get split up on the '&'.
					$inQueryStr = str_replace( '&amp;', '%26', $value );

					parse_str( $inQueryStr, $arr );
					$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
					break;

				case 'ok text':
				case 'error text':
					// do not parse ok text or error text yet. Will be parsed on api call
					$arr = array( $key => $value );
					$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
					break;

				case 'target':
				case 'title':
					$value = $parser->recursiveTagParse( $value );
					$arr = array( $key => $value );
					$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );

					$targetTitle = Title::newFromText( $value );

					if ( $targetTitle !== null ) {
						$targetArticle = new Article( $targetTitle );
						$targetArticle->clear();
						$editTime = $targetArticle->getTimestamp();
					}

				default :

					$value = $parser->recursiveTagParse( $value );
					$arr = array( $key => $value );
					$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			}
		}

		// query string has to be turned into hidden inputs.
		if ( !empty( $inQueryArr ) ) {

			$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );

			foreach ( $query_components as $query_component ) {
				$var_and_val = explode( '=', $query_component, 2 );
				if ( count( $var_and_val ) == 2 ) {
					$formcontent .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
				}
			}
		}

		if ( $linkString == null ) return null;

		if ( $linkType == 'button' ) {
			// Html::rawElement() before MW 1.21 or so drops the type attribute
			// do not use Html::rawElement() for buttons!
			$linkElement = '<button ' . Html::expandAttributes( array( 'type' => 'submit', 'class' => $classString ) ) . '>' . $linkString . '</button>';
		} elseif ( $linkType == 'link' ) {
			$linkElement = Html::rawElement( 'a', array( 'class' => $classString, 'href' => "#" ), $linkString );
		} else {
			$linkElement = Html::rawElement( 'span', array( 'class' => $classString ), $linkString );
		}

		if ( $summary == null ) {
			$summary = wfMessage( 'sf_multiedit_summary', "[[{$parser->getTitle()}]]" )->text();
		}

		$formcontent .= Html::hidden( 'wpSummary', $summary );

		if ( $editTime !== null ) {
			$formcontent .= Html::hidden( 'wpEdittime', $editTime );
		}

		$form = Html::rawElement( 'form', array( 'class' => 'multiedit-data' ), $formcontent );

		// ensure loading of jQuery and style sheets
		self::loadScriptsFormultiedit( $parser );

		$output = Html::rawElement( 'div', array( 'class' => 'multiedit' ),
				$linkElement .
				Html::rawElement( 'span', array( 'class' => "multiedit-result" ), null ) .
				$form
		);

		// return output HTML
		return $parser->insertStripItem( $output, $parser->mStripState );
	}

	/**
	 * Load scripts and style files for multiedit
	 */
	private static function loadScriptsFormultiedit ( &$parser ) {
		global $sfgScriptPath;

		if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) ) {
			$parser->getOutput()->addModules( 'ext.semanticforms.multiedit' );
		} else {

			static $loaded = false;

			// load JavaScript and CSS files only once
			if ( !$loaded ) {

				// load extensions JavaScript
				$parser->getOutput()->addHeadItem(
					'<script type="text/javascript" src="' . $sfgScriptPath
					. '/libs/SF_multiedit.js"></script> ' . "\n",
					'sf_multiedit_script'
				);

				// load extensions style sheet
				$parser->getOutput()->addHeadItem(
					'<link rel="stylesheet" href="' . $sfgScriptPath
					. '/skins/SF_multiedit.css"/> ' . "\n",
					'sf_multiedit_style'
				);

				$loaded = true;
			}
		}

		return true;
	}

}

