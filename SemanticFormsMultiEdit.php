<?php

/** Definition based on Semantic Forms **/

if ( !defined( 'MEDIAWIKI' ) ) die();

// You need Semantic Forms
if ( ! defined( 'SF_VERSION' ) ) die();

define( 'SFME_VERSION', '1.0' );

$GLOBALS['wgExtensionCredits'][defined( 'SEMANTIC_EXTENSION_TYPE' ) ? 'semantic' : 'specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic Forms MultiEdit',
	'version' => SFME_VERSION,
	'author' => array( 'Yaron Koren', 'Stephan Gambke', 'Toniher' ),
	'url' => 'https://github.com/ProteoWiki/SemanticFormsMultiEdit',
	'descriptionmsg' => 'semanticforms-multiedit-desc',
	'license-name' => 'GPL-2.0+'
);


$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SFMEParserFunctions::registerFunctions';

$GLOBALS['wgAPIModules']['sfmultiedit'] = 'SFMultieditAPI';

$GLOBALS['wgAutoloadClasses']['SFMultieditAPI'] = __DIR__ . '/includes/SF_MultieditAPI.php';
$GLOBALS['wgAutoloadClasses']['SFMEParserFunctions'] = __DIR__ . '/includes/SFME_ParserFunctions.php';

$GLOBALS['wgMessagesDirs']['SemanticFormsMultiEdit'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['SemanticFormsMultiEdit'] = __DIR__ . '/languages/SFME_Messages.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticFormsMultiEditAlias'] = __DIR__ . '/languages/SFME_Aliases.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticFormsMultiEditMagic'] = __DIR__ . '/languages/SFME_Magic.php';


// register client-side modules
if ( defined( 'MW_SUPPORTS_RESOURCE_MODULES' ) ) {
	$sfgResourceTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'SemanticFormsMultiEdit'
	);
	$GLOBALS['wgResourceModules'] += array(
		'ext.semanticforms.autoedit' => $sfgResourceTemplate + array(
			'scripts' => 'libs/SF_autoedit.js',
			'styles' => 'skins/SF_autoedit.css',
			'messages' => array(
				'sf-autoedit-wait',
				'sf_autoedit_anoneditwarning',
			),
		)
	);
}



