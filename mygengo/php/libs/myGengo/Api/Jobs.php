<?php
/**
 * myGengo API Client
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that came
 * with this package in the file LICENSE.txt. It is also available
 * through the world-wide-web at this URL:
 * http://mygengo.com/services/api/dev-docs/mygengo-code-license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@mygengo.com so we can send you a copy immediately.
 *
 * @category   myGengo
 * @package    API Client Library
 * @copyright  Copyright (c) 2009-2010 myGengo, Inc. (http://mygengo.com)
 * @license    http://mygengo.com/services/api/dev-docs/mygengo-code-license   New BSD License
 */

class myGengo_Api_Jobs extends myGengo_Api
{
    public function __construct($api_key = null, $private_key = null)
    {
        parent::__construct($api_key, $private_key);
    }

    /**
     * translate/jobs (POST)
     *
     * Submits a job or group of jobs to translate.
     *
     * @param array|array|string $jobs An array of payloads (a payload being itself an array of string)
     * of jobs to create.
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function postJobs($jobs, $as_group = 0, $process = 1, $format = null)
    {
        $data = array('jobs' => $jobs,
                'as_group' => ($as_group)? '1' : '0'//,
//                'process' => ($process)? 'true' : 'false'
		);
        // create the query
        $params = array('api_key' => $this->config->get('api_key', null, true), 
                'ts' => gmdate('U'),
                'data' => json_encode($data));
        // sort and sign
        ksort($params);
        $enc_params = json_encode($params);
        $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));

	if (is_null($format))
	        $format = $this->config->get('format', null, true);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= 'translate/jobs';
        $this->response = $this->client->post($baseurl, $format, $params);
    }

    /**
     * translate/jobs (GET)
     *
     * Retrieves a list of resources for the most recent jobs filtered
     * by the given parameters.
     *
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getJobs($ids = null, $format = null, $params = null)
    {
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= 'translate/jobs';
        if (!is_null($ids))
        {
            $baseurl .= '/' . implode(',', $ids);
        }
	if (is_null($format))
	        $format = $this->config->get('format', null, true);

        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/jobs/{id} (GET)
     *
     * Retrieves the group of jobs that were previously submitted
     * together.
     *
     * @param int $id The id of the job group to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getGroupedJobs($id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/jobs/group/{$id}";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/jobs (PUT)
     *
     * Updates jobs to translate. returns these jobs back to the translators for revisions.
     *
     * @param array|array|string $jobs (required) the payloads to identify the jobs sent back for revisions:
     *  - "comment" (required) the reason to the translator for sending the job back for revisions.
     *      AND
     *  One of the following format for job identification (note: all jobs must have the same format):
     *  - "job_id" the job's id
            OR
     *  - "body_src", the original body of text to be translated,
     *  - "lc_src", the source language code,
     *  - "lc_tgt", the target language code.
     *  
     */
    public function revise($jobs)
    {
        // pack the jobs
        $data = array('action' => 'revise');
        $first_job = current($jobs);
        if (isset($first_job['job_id']))
        {
            $data['job_ids'] = $jobs;
        }
        else
            $data['jobs'] = $jobs;

        // create the query
        $params = array('api_key' => $this->config->get('api_key', null, true),
                'ts' => gmdate('U'),
                'data' => json_encode($data));
        // sort and sign
        ksort($params);
        $enc_params = json_encode($params);
        $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));

        $format = $this->config->get('format', null, true);
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/jobs";
        $this->response = $this->client->put($baseurl, $format, $params);
    }
    
    /**
     * translate/jobs (PUT)
     *
     * Updates jobs to translate. Approves jobs.
     *
     * @param array|array|string $jobs (required) the payloads to identify the jobs sent back for revisions:
     *  - "rating" (optional) the reason to the translator for sending the job back for revisions.
     *  - "for_translator" (optional) - comments for the translator
     *  - "for_mygengo" (optional) - comments for myGengo staff (private)
     *  - "public" (optional) - 1 (true) / 0 (false, default); whether myGengo can share this feedback publicly
     *      AND
     *  One of the following format for job identification (note: all jobs must have the same format):
     *  - "job_id" the job's id
            OR
     *  - "body_src", the original body of text to be translated,
     *  - "lc_src", the source language code,
     *  - "lc_tgt", the target language code.
     *  
     */
    public function approve($jobs)
    {
        $data = array('action' => 'approve');
        $first_job = current($jobs);
        if (isset($first_job['job_id']))
        {
            $data['job_ids'] = $jobs;
        }
        else
        {
            $data['jobs'] = $jobs;
        }

        // create the query
        $params = array('api_key' => $this->config->get('api_key', null, true),
                'ts' => gmdate('U'),
                'data' => json_encode($data));
        // sort and sign
        ksort($params);
        $enc_params = json_encode($params);
        $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));

        $format = $this->config->get('format', null, true);
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/jobs";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/jobs (PUT)
     *
     * Updates jobs to translate. returns these jobs back to the translators for revisions.
     *
     * @param array|array|string $jobs (required) the payloads to identify the jobs sent back for revisions:
     *  - reason (required) - "quality", "incomplete", "other"
     *  - comment (required)
     *  - captcha (required) - the captcha image text. Each job in a "reviewable" state will
     *  - have a captcha_url value, which is a URL to an image.  This
     *  - captcha value is required only if a job is to be rejected.
     *  - follow_up (optional) - "requeue" (default) or "cancel"
     *      AND
     *  One of the following format for job identification (note: all jobs must have the same format):
     *  - "job_id" the job's id
            OR
     *  - "body_src", the original body of text to be translated,
     *  - "lc_src", the source language code,
     *  - "lc_tgt", the target language code.
     *  
     */
    public function reject($jobs)
    {
        $data = array('action' => 'reject');
        $first_job = current($jobs);
        if (isset($first_job['job_id']))
        {
            $data['job_ids'] = $jobs;
        }
        else
            $data['jobs'] = $jobs;

        // create the query
        $params = array('api_key' => $this->config->get('api_key', null, true),
                'ts' => gmdate('U'),
                'data' => json_encode($data));
        // sort and sign
        ksort($params);
        $enc_params = json_encode($params);
        $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));

        $format = $this->config->get('format', null, true);
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/jobs";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (DELETE)
     *
     * Cancels the jobs. You can only cancel a job if it has not been
     * started already by a translator.
     *
     * @param array|int (required) $ids Array of ids of the jobs to cancel
     */
    public function cancel($ids)
    {
        $data = array('job_ids' => $ids);
        $params = array('api_key' => $this->config->get('api_key', null, true), 'ts' => gmdate('U'), 'data'=> json_encode($data));
        ksort($params);
        $query = http_build_query($params);
        $hmac = hash_hmac('sha1', $query, $this->config->get('private_key', null, true));
        $params['api_sig'] = $hmac;

        $format = $this->config->get('format', null, true);
        $this->setParamsNotId($format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/jobs";
        $this->response = $this->client->delete($baseurl, $format, $params);
    }
}
