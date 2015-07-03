# SemanticFormsMultiEdit
Add multiedit parser function to Semantic Forms

// Max number of pages than can be created at once

    $GLOBALS['wgSFMEMaxPages'] = 100;

## Example Syntax

    {{#multiedit:target=Sample:XXX_|form=Sample|query string=Sample[Batch name]=Mybatch&Sample[Request Reference]={{FULLPAGENAME}}&Sample[Creation date]={{CURRENTMONTHABBREV}} {{CURRENTDAY2}} {{CURRENTYEAR}} {{CURRENTTIME}}:{{CURRENTSECOND}}|start=0|end=10|digits=2|overwrite=0|link text='''Add samples'''|ok text=Samples created|reload|mail={{{User_Name|}}}|checkbase=\d{6}_S_\D{4}|origin={{FULLPAGENAME}}}}}}|All samples are created}}

### Parameters

* target: Base name of the base
* form: Form to be used
* query_string: Data to be passed to the form
* start: First iteration number
* end: Last iteration number
* digits: Number of digits of the iteration numbers
* link text: Text of the link generated
* ok text: Text once pages are successfully created
* reload: Whether to reload hosting page once done
* mail: ... TODO ...
* checkbase: Extra check for avoiding pages to be created that do not follow the given regex
* origin: Reference for origin pages

