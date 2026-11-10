<?php
/**
 * Opensource karst Information System
 * 
 * 
 * not modify this file
 * all variables are overwritten by values in the database
 * in misc/fndatabase/sections_news/settings.php
 * this file contains only the default values
 * 
 */
//[i18n]Map default latitude
$config['default_latitude']=44;
//[i18n]Map default longitude
$config['default_longitude']=8;
//[i18n]Map default zoom
$config['default_zoom']=8;
//[i18n]AI service: enabled (shows the AI buttons) {1=yes,0=no}
$config['ai_enabled']="0";
//[i18n]AI service: API key (OpenAI compatible)
$config['ai_apikey']=""; // {password}
//[i18n]AI service: API address (OpenAI compatible)
$config['ai_url']="https://api.openai.com/v1";
//[i18n]AI service: model
$config['ai_model']="gpt-4o-mini";
//[i18n]AI service: reasoning effort (reasoning models only) {default=not sent,none=none (quick reply),low=low effort,medium=medium effort,high=high effort}
$config['ai_reasoning_effort']="default";





?>