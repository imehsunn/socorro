<?php defined('SYSPATH') or die('No direct script access.');
require_once dirname(__FILE__).'/../libraries/bugzilla.php';
require_once dirname(__FILE__).'/../libraries/MY_SearchReportHelper.php';
require_once dirname(__FILE__).'/../libraries/MY_QueryFormHelper.php';

/**
 *
 */
class Query_Controller extends Controller {

    public function __construct()
    {
        parent::__construct();
	$this->bug_model = new Bug_Model;
    }

    /**
     *
     */
    public function query() {
      //Query Form Stuff
        $searchHelper = new SearchReportHelper;
        $queryFormHelper = new QueryFormHelper;

	$queryFormData = $queryFormHelper->prepareCommonViewData($this->branch_model, $this->platform_model);
	$this->setViewData($queryFormData);

	//Current Query Stuff
        $params = $this->getRequestParameters($searchHelper->defaultParams());
        $searchHelper->normalizeParams( $params );

        $signature_to_bugzilla = array();

        cachecontrol::set(array(
            'etag'     => $params,
            'expires'  => time() + ( 60 * 60 )
        ));


        if ($params['do_query'] !== FALSE) {
            $reports = $this->common_model->queryTopSignatures($params);
            $signatures = array();
            foreach ($reports as $report) {
	          array_push($signatures, $report->signature);
	    }
	    Kohana::log('info', "I am in query");
            $rows = $this->bug_model->bugsForSignatures(array_unique($signatures));
	    $bugzilla = new Bugzilla;
	    $bugzillaUrl = Kohana::config('codebases.bugTrackingUrl');
            foreach ($rows as $row) {
	      if ( ! array_key_exists($row['signature'], $signature_to_bugzilla)) {
	  	  $signature_to_bugzilla[$row['signature']] = array();
	      }
	      $row['open'] = empty($row['resolution']);
	      $row['url'] = $bugzillaUrl . $row['id'];
	      $row['summary'] = "Summary Coming soon";

	      array_push($signature_to_bugzilla[$row['signature']], $row);
	    }
	    foreach ($signature_to_bugzilla as $k => $v) {
	        $bugzilla->sortByResolution($signature_to_bugzilla[$k]);
	    }
	      
        } else {
            $reports = array();

        }

	//common getParams
	//prepareAndSetCommonViewData($params)

        // TODO: redirect if there's one resulting report signature group


        $this->setViewData(array(
            'params'  => $params,
            'queryTooBroad' => $searchHelper->shouldShowWarning(),
            'reports' => $reports,
            'sig2bugs' => $signature_to_bugzilla
	 ));
    }

}
