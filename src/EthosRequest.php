<?php

namespace Roanokecollege\Ethos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class EthosRequest {

  public function __construct () {
    $this->api_url = config("ethos.proxy_url") . "api/";

    try {
      $auth_token_response = Http::withToken(config("ethos.api_key"))->post(config("ethos.proxy_url") . "auth");
      $auth_token_response->throw();
      $this->auth_token = $auth_token_response->body();
    } catch (\Exception $e) {
      $exception = new \Exception("Unable to retrieve authorization token.  Please check your .env vars and try again");
      throw $exception;
    }
  }

  private function generateUrl (string $api_endpoint, string $guid = "") {
    $url = $this->api_url . $api_endpoint;
    if (!empty($guid)) {
      $url .= "/" . $guid;
    }

    return $url;
  }

  /**
   * Get Colleague record of a person by their RCID.
   * This function gets you a Colleague record by specifying a persons name.  This is most useful for getting a users GUID, but includes other personal data of the user.
   *
   * @param string $rcid The RCID of the user the record being requested.
   * @return object A std_class object with all relevant colleague information for the requested user.
   **/
  public function getPersonRecordByRCID (string $rcid): object {
    return Http::withToken($this->auth_token)->get($this->generateUrl("persons"), ["criteria" => "{'credentials':[{'type':'colleaguePersonId', 'value': '$rcid'}]}"])->object()[0];
  }

  /**
   * Get a list of active PERCs for a student by GUID.
   *
   * @param string $guid The GUID of the person record we are trying to get the PERCs for.
   * @return Collection A Laravel Collection of PERC entries for the specified student.
   **/
  public function getPersonHoldsByPersonGUID (string $guid): Collection {
    return collect(Http::withToken($this->auth_token)->get($this->generateUrl("person-holds"), ["person" => $guid])->json());
  }

  /**
   * Get a list of active PERCs for a student by RCID.
   *
   * @param string $rcid The RCID of the person record we are trying to get the PERCs for.
   * @return Collection A Laravel collection of active PERC entries for the specified student.
   **/
  public function getPersonHoldsByRCID (string $rcid): Collection {
    $person_record = $this->getPersonRecordByRCID ($rcid);
    return $this->getPersonHoldsByPersonGUID ($person_record->id);
  }

  /**
   * Get a specific PERC for a student by specifying the person-hold-type GUID
   *
   * @param string $rcid The RCID of the person record we are checking the PERCs for.
   * @param string $guid The GUID of the person-hold-type entry of the PERC we are checking for.
   * @return mixed Null if the student does not have the specified perc active.  Otherwise, an array representing the PERC details.
   **/
  public function getPersonHoldByRCIDAndPercGUID (string $rcid, string $guid) {
    return $this->getPersonHoldsByRCID($rcid)->where("type.detail.id", $guid)->first();
  }

  /**
   * Update a PERC.
   *
   * @param array $person_hold an array of attributes to update a hold in colleague.
   *              MUST HAVE AN id ATTRIBUTE, which is the GUID of the PERC entry to be updated.
   * @throws Exception If a PERC entry cannot be found for the specified ID.
   **/
  public function updatePersonHoldByEntry (array $person_hold) {
    Http::withToken($this->auth_token)
          ->withHeaders([
            "Accept" => config("ethos.api_header"),
            "Content-Type" => config("ethos.api_header")
          ])
          ->withBody(json_encode($person_hold), "text/json")
          ->put($this->generateUrl("person-holds", $person_hold["id"]))
          ->throw();
  }

  /**
   * Get the GUID for a specified PERC.
   *
   * @param string $perc The string pneumonic for a specified hold.
   * @return string The GUID to use for the provided PERC
   * @throws Exception Unable to find PERC
   **/
  public function getPercGuidByCode (string $perc): string {
      $response = Http::withToken($this->auth_token)
                      ->withHeaders([
                        "accept" => config("ethos.api_header"),
                        "Content-Type" => config("ethos.api_header")
                      ])->get($this->generateUrl("person-hold-types"))
                      ->throw();
      $response = collect($response->object())->where("code", $perc)->first()->id;
      return $response;
  }

  /**
   * End a PERC.
   *
   * @param string $rcid The RCID of the person record we are checking the PERCs for.
   * @param string $guid The GUID of the person-hold-type entry of the PERC we are checking for.
   * @param string $comment _Optional_ A comment to set on the perc, if provided.
   * @throws Exception If a PERC entry cannot be found for the specified ID.
   **/
  public function endPersonHoldByRCIDAndPercGuid (string $rcid, string $guid, string $comment = "") {
    $response = $this->getPersonHoldByRCIDAndPercGUID($rcid, $guid);
    $response["endOn"] = \Carbon\Carbon::now();
    if (!empty($comment)) $response["comment"] = $comment;
    $this->updatePersonHoldByEntry($response);
  }

  /**
   * End a PERC by code.
   *
   * @param string $rcid The RCID of the person record we are checking the PERCs for.
   * @param string $perc The string pneumonic for a specified hold.
   * @param string $comment _Optional_ A comment to set on the perc, if provided.
   * @throws Exception If a PERC entry cannot be found for the specified ID.
   **/
  public function endPersonHoldByRCIDAndPercCode (string $rcid, string $perc, string $comment = "") {
    $guid = $this->getPercGuidByCode($perc);
    $this->endPersonHoldByRCIDAndPercGuid($rcid, $perc, $comment);
  }

}
