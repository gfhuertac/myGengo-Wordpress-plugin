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

class myGengo_Api_Job extends myGengo_Api
{
    public function __construct($api_key = null, $private_key = null)
    {
        parent::__construct($api_key, $private_key);
    }

    /**
     * translate/job/{id} (GET)
     *
     * Retrieves a specific job
     *
     * @param int $id The id of the job to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getJob($id = null, $format = null, $params = null)
    {
	$this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/comments (GET)
     *
     * Retrieves the comment thread for a job
     *
     * @param int $id The id of the job to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getComments($id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/comments";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/feedback (GET)
     *
     * Retrieves the feedback
     *
     * @param int $id The id of the job to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getFeedback($id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/feedback";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/revisions (GET)
     *
     * Gets list of revision resources for a job.
     *
     * @param int $id The id of the job to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getRevisions($id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/revisions";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/revision/{rev_id}
     *
     * Gets specific revision for a job.
     *
     * @param int $id The id of the job to retrieve
     * @param int $rev_id The id of the revision to retrieve
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function getRevision($id = null, $rev_id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        if (is_null($rev_id))
        {
            $rev_id = $this->config->get('rev_id', null, true);
        }
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/revision/{$rev_id}";
        $this->response = $this->client->get($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (PUT)
     *
     * Updates a job to translate. Pay for the job.
     *
     * @param int $id The id of the job to revise
     */
    public function purchase($id)
    {
        if (!empty($id))
        {
            // pack the jobs
            $data = array('action' => 'purchase');

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true),
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }
        else 
        {
            throw new myGengo_Exception(
                    sprintf('In method %s: "id" is required', __METHOD__)
                    );
        }

        $format = $this->config->get('format', null, true);
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (PUT)
     *
     * Updates a job to translate. returns this job back to the translator for revisions.
     *
     * @param int $id The id of the job to revise
     * @param string $comment (required) the reason to the translator for sending the job back for revisions.
     */
    public function revise($id, $comment)
    {
        if (!(empty($id) || empty($comment)))
        {
            // pack the jobs
            $data = array('action' => 'revise', 'comment' => $comment);

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true),
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }
        else 
        {
            throw new myGengo_Exception(
                    sprintf('In method %s: "id" and "comment" are required', __METHOD__)
                    );
        }

        $format = $this->config->get('format', null, true);
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (PUT)
     *
     * Updates a job to translate. Approves job.
     *
     * @param int $id The id of the job to approve
     * @param array|string $args contains the parameters for the approval:
     *  rating (required) - 1 (poor) to 5 (fantastic)
     *  for_translator (optional) - comments for the translator
     *  for_mygengo (optional) - comments for myGengo staff (private)
     *  public (optional) - 1 (true) / 0 (false, default); whether myGengo can share this feedback publicly
     */
    public function approve($id, $args, $format = 'json')
    {
        if (!is_null($id))
        {
            if (isset($args['rating']) && !(is_numeric($args['rating']) && $args['rating'] >= 1 && $args['rating'] <= 5)) {
                throw new myGengo_Exception(
                        sprintf('In method %s: "params" must contain a valid rating', __METHOD__)
                        );
            }

            // pack the jobs
            $data = array('action' => 'approve', 'public' => (isset($args['public']) && !empty($public))? 1 : 0);
            if (!is_null($args['rating']))
            {
                $data['rating'] = $args['rating'];
            }
            if (!is_null($args['for_translator']))
            {
                $data['for_translator'] = $args['for_translator'];
            }
            if (!is_null($args['for_mygengo']))
            {
                $data['for_mygengo'] = $args['for_mygengo'];
            }

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true),
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }
        else 
        {
            throw new myGengo_Exception(
                    sprintf('In method %s: "id" is required.', __METHOD__)
                    );
        }

        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (PUT)
     *
     * Updates a job to translate. rejects the translation
     *
     * @param int $id The id of the job to reject
     * @param string $format The response format, xml or json
     * @param array|string $args contains the parameters for the rejection:
     *  reason (required) - "quality", "incomplete", "other"
     *  comment (required)
     *  captcha (required) - the captcha image text. Each job in a "reviewable" state will
     *  have a captcha_url value, which is a URL to an image.  This
     *  captcha value is required only if a job is to be rejected.
     *  follow_up (optional) - "requeue" (default) or "cancel"
     */
    public function reject($id, $args, $format = 'json')
    {
        if (!empty($id) && isset($args['reason']) && isset($args['comment']) && isset($args['captcha']))
        {
            $reason = $args['reason'];
            $comment = $args['comment'];
            $captcha = $args['captcha'];

            $valid_reasons = array("quality", "incomplete", "other");
            if (!in_array($reason, $valid_reasons))
            {
                throw new myGengo_Exception(
                        sprintf('In method %s: "params" must contain a valid reason', __METHOD__)
                        );
            }
            // pack the jobs
            $data = array('action' => 'reject', 'reason' => $reason, 'comment' => $comment, 'captcha' => $captcha);

            $valid_follow_ups = array("requeue", "cancel");
            if (isset($args['follow_up']))
            {
                if (!in_array($args['follow_up'], $valid_follow_ups))
                {
                    throw new myGengo_Exception(
                            sprintf('In method %s: if set, "params" must contain a valid follow up', __METHOD__)
                            );
                }
                $data['follow_up'] = $args['follow_up'];
            }

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true),
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }
        else 
        {
            throw new myGengo_Exception(
                    sprintf('In method %s: "id" is required and "args" must contain a reason, a comment and a captcha', __METHOD__)
                    );
        }

        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->put($baseurl, $format, $params);
    }

    /**
     * translate/job
     *
     * Post a new job for translation
     *
     * @param array|string a job payload.
     * @param string $format The response format, xml or json
     * @param array|string $params Should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function postJob($job, $format = null, $params = null)
    {
        // XXX: there is no check that $job is a valid payload.
        if (!is_null($job)) // If $job is not null, I override $params.
        {
            // pack the jobs
            $data = array('job' => $job);

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true), '_method' => 'post',
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }

        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= 'translate/job';
        if (is_null($format))
        {
            $format = $this->config->get('format', null, true);
        }
        $this->response = $this->client->post($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/comment (POST)
     *
     * Submits a new comment to the job's comment thread.
     *
     * @param int $id The id of the job to comment on
     * @param string $body The comment's actual contents.
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function postComment($id = null, $body = null, $format = null, $params = null)
    {
        if (!(is_null($id) || is_null($body))) // If nor the id or the body are null, we override params.
        {
            // pack the jobs
            $data = array('body' => $body);

            // create the query
            $params = array('api_key' => $this->config->get('api_key', null, true), '_method' => 'post',
                    'ts' => gmdate('U'),
                    'data' => json_encode($data));
            // sort and sign
            ksort($params);
            $enc_params = json_encode($params);
            $params['api_sig'] = myGengo_Crypto::sign($enc_params, $this->config->get('private_key', null, true));
        }

        if (empty($params)) {
            throw new myGengo_Exception(
                sprintf('In method %s: "params" must contain a valid "body" parameter as the comment', __METHOD__)
                );
        }
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/comment";
        $this->response = $this->client->post($baseurl, $format, $params);
    }

    /**
     * translate/job/{id} (DELETE)
     *
     * Cancels the job. You can only cancel a job if it has not been
     * started already by a translator.
     *
     * @param int $id The id of the job to cancel
     * @param string $format The response format, xml or json
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function cancel($id, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}";
        $this->response = $this->client->delete($baseurl, $format, $params);
    }

    /**
     * translate/job/{id}/preview (GET)
     *
     * Renders a JPEG preview of the translated text
     * N.B. - if the request is valid, a raw JPEG stream is returned.
     *
     * @param int $id The id of the job, if not passed it should be in config
     * @param string $format The response format, xml or json (in case of error)
     * @param array|string $params If passed should contain all the
     * necessary parameters for the request including the api_key and
     * api_sig
     */
    public function previewJob($id = null, $format = null, $params = null)
    {
        $this->setParams($id, $format, $params);
        $baseurl = $this->config->get('baseurl', null, true);
        $baseurl .= "translate/job/{$id}/preview";
        $this->response = $this->client->get($baseurl, $format, $params);
    }
}

