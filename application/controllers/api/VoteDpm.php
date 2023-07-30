<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Use RESTFul API server
use chriskacerguis\RestServer\RestController;

class VoteDpm extends RestController
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Api_model');
    }

    /**
     * Mendapatkan Data Kandidat API
     * -----------------------------
     * @method : GET
     * @link: api/vote/kandidat
     */

    public function kandidat_get()
    {
        header("Access-Control-Allow-Origin: *");

        // Load Authorization Token Library
        $this->load->library('Authorization_Token');

        /**
         * User Token Validation
         */
        $is_valid_token = $this->authorization_token->validateToken();
        if (!empty($is_valid_token) and $is_valid_token['statusdpm'] === TRUE) {
            // Get All Kandidat
            $kandidat_data_dpm = $this->Api_model->get_all('nourut', 'kandidatdpm', 'ASC');

            $return_data = [
                'kandidat_data_dpm' => $kandidat_data_dpm
            ];
            // baca token userData
            $userData = $this->authorization_token->userData();

            if ($userData->statusdpm === "Sudah Memilih") {
                $message = [
                    'statusdpm' => false,
                    'message' => 'User sudah memilih',
                ];
                $this->response($message, 200);
            } else {
                $message = [
                    'statusdpm' => true,
                    'data' => $return_data,
                    'message' => "Request successful",
                ];
                $this->response($message, 200);
            }
        } else {
            $this->response(['statusdpm' => false, 'message' => $is_valid_token['message']], 404);
        }
    }

    /**
     * Vote API
     * --------------------
     * @param: idkandidat
     * @param: idpemilih
     * --------------------------
     * @method : POST
     * @link: api/user/login
     */

    public function vote_post()
    {
        header("Access-Control-Allow-Origin: *");
        // Load Authorization Token Library
        $this->load->library('Authorization_Token');

        /**
         * User Token Validation
         */
        $is_valid_token = $this->authorization_token->validateToken();
        if (!empty($is_valid_token) and $is_valid_token['statusdpm'] === TRUE) {
            # Input vote kedalam database

            # XSS Filtering (https://www.codeigniter.com/user_guide/libraries/security.html)
            $_POST = $this->security->xss_clean($_POST);

            # Form Validation
            $this->form_validation->set_rules('idkandidatdpm', 'idkandidatdpm', 'trim|required|max_length[50]');
            $this->form_validation->set_rules('idpemilih', 'idpemilih', 'trim|required|max_length[50]');

            if ($this->form_validation->run() == FALSE) {
                // Form Validation Errors
                $message = array(
                    'statusdpm' => false,
                    'error' => $this->form_validation->error_array(),
                    'message' => validation_errors()
                );

                $this->response($message, 404);
            } else {

                /*  HTTP_OK = 200;
                *   HTTP_CREATED = 201;
                *   HTTP_NOT_MODIFIED = 304;
                *   HTTP_BAD_REQUEST = 400;
                *   HTTP_UNAUTHORIZED = 401;
                *   HTTP_FORBIDDEN = 403;
                *   HTTP_NOT_FOUND = 404;
                *   HTTP_NOT_ACCEPTABLE = 406;
                *   HTTP_INTERNAL_ERROR = 500;
                */
                $idpemilih = $this->input->post('idpemilih');
                $idkandidat = $this->input->post('idkandidatdpm');

                // baca token userData
                $userData = $this->authorization_token->userData();

                // Cek idpemilih sama dengan iduser didalam token
                if ($userData->id == $idpemilih) {
                    $dataPemilih = $this->Api_model->get_by_id('id', $idpemilih, 'data_pemilih');
                    $dbStatus =  $dataPemilih->statusdpm;
                    // Cek apakah user belum memilih dalam token
                    if ($userData->statusdpm == "Belum Memilih" && $dbStatus == "Belum Memilih") {

                        // insertData
                        $insertData = array(
                            'tipe' => $userData->level,
                            'idpemilih' => $idpemilih,
                            'idkandidatdpm' => $idkandidat,
                        );

                        // Insert data
                        $this->Api_model->insert('data_pemilihan_dpm', $insertData);

                        // Update data
                        $updateData = array(
                            'statusdpm' => 'Sudah Memilih'
                        );

                        // Update database
                        $this->Api_model->update('id', $idpemilih, 'data_pemilih', $updateData);

                        //Success
                        $message = [
                            'statusdpm' => true,
                            'message' => "Vote successful"
                        ];
                        $this->response($message, 200);
                    } else {
                        $message = array(
                            'statusdpm' => false,
                            'error' => 403,
                            'message' => 'user sudah memilih'
                        );
                    }
                } else {
                    $message = array(
                        'statusdpm' => false,
                        'error'  => 406,
                        'message' => 'invalid token'
                    );
                    $this->response($message, 406);
                }
            }
        } else {
            $this->response(['statusdpm' => false, 'message' => $is_valid_token['message']], 404);
        }
    }
}
