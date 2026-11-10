<?php

/**
 * Client of the AI service (any OpenAI compatible chat completions API).
 *
 * Key, address and model are set by the administrator in the configuration
 * section (configopenkis, extra/openkis/config.php: ai_apikey, ai_url,
 * ai_model, ai_reasoning_effort). Sites configured before that section had them keep working
 * with $_FN['openai_apikey'] / $_FN['openai_model'] in openkis_config.local.php.
 * The key never goes in the code: the repositories are public.
 */

/**
 * AI service settings.
 *
 * @global array $_FN
 * @return array apikey, url, model, reasoning_effort ("" apikey = feature
 *               disabled, "" reasoning_effort = not sent)
 */
function openkis_AiSettings()
{
    global $_FN;
    static $settings = null;
    if ($settings !== null)
    {
        return $settings;
    }
    $config = FN_LoadConfig("extra/openkis/config.php");
    $value = function ($key, $legacy, $default) use ($config, $_FN)
    {
        if (isset($config[$key]) && trim((string) $config[$key]) !== "")
        {
            return trim((string) $config[$key]);
        }
        if ($legacy !== "" && !empty($_FN[$legacy]))
        {
            return trim((string) $_FN[$legacy]);
        }
        return $default;
    };
    $settings = array(
        "apikey" => $value("ai_apikey", "openai_apikey", ""),
        // base address of the API (https://api.openai.com/v1, https://ollama.com/v1, ...)
        // or the full chat completions endpoint
        "url" => preg_replace('#(/chat/completions)?/*$#', '', $value("ai_url", "", "https://api.openai.com/v1")) . "/chat/completions",
        "model" => $value("ai_model", "openai_model", "gpt-4o-mini"),
        // sent only when set: models without reasoning reject the parameter
        "reasoning_effort" => in_array($value("ai_reasoning_effort", "", ""), array("none", "low", "medium", "high"), true) ? $value("ai_reasoning_effort", "", "") : "",
    );
    return $settings;
}

/**
 * Sends one user message to the AI service.
 *
 * @param string|array $content text, or the list of content parts of the
 *                              chat completions API (text and image_url)
 * @param int          $max_tokens reasoning models spend part of it before the
 *                                 answer: a low limit gives an empty reply
 * @return array ok, content (reply text), model, error (message for the user)
 */
function openkis_AiChat($content, $max_tokens = 16000)
{
    $settings = openkis_AiSettings();
    $ret = array("ok" => false, "content" => "", "model" => $settings['model'], "error" => "");
    if ($settings['apikey'] === "")
    {
        $ret['error'] = FN_Translate("AI service not configured (Configuration section)");
        return $ret;
    }
    if (!function_exists("curl_init"))
    {
        $ret['error'] = "curl not available";
        return $ret;
    }
    $payload = array(
        "model" => $settings['model'],
        "messages" => array(array("role" => "user", "content" => $content)),
        "max_tokens" => intval($max_tokens),
    );
    if ($settings['reasoning_effort'] !== "")
    {
        $payload['reasoning_effort'] = $settings['reasoning_effort'];
    }
    set_time_limit(180);
    $ch = curl_init($settings['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $settings['apikey'],
    ));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    // no curl_close(): a no-op since PHP 8.0, deprecated in 8.5
    if ($response === false)
    {
        $ret['error'] = FN_Translate("network error") . ": $curl_error";
        return $ret;
    }
    $decoded = json_decode($response, true);
    if ($http_code !== 200)
    {
        // the API error message, never the raw payload
        $ret['error'] = "HTTP $http_code" . (isset($decoded['error']['message']) ? ": " . $decoded['error']['message'] : "");
        return $ret;
    }
    if (!isset($decoded['choices'][0]['message']['content']) || trim((string) $decoded['choices'][0]['message']['content']) === "")
    {
        $ret['error'] = FN_Translate("invalid AI reply");
        return $ret;
    }
    $ret['ok'] = true;
    $ret['content'] = $decoded['choices'][0]['message']['content'];
    return $ret;
}

/**
 * JSON object of a reply: the whole text, or the outermost {...} block when
 * the model wraps it in other text or a code fence.
 *
 * @param string $content
 * @return array|null
 */
function openkis_AiJson($content)
{
    $data = json_decode(trim($content), true);
    if (!is_array($data) && preg_match('/\{.*\}/s', $content, $matches))
    {
        $data = json_decode($matches[0], true);
    }
    return is_array($data) ? $data : null;
}
