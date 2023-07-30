<?php


defined('BASEPATH') or exit('No direct script access allowed');

class DpmVote extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('ion_auth', 'form_validation', 'session');
        $this->load->helper('url', 'language');
        $this->load->model('Dpm_model');
    }
    public function index()
    {
             // Security check if the user is authorize
             if (cek_login_bol   ()) {
                redirect('votedpm', 'refresh');
            }
    
            $data['title'] = 'E-Voting';
            $data['action'] = site_url('user/Userauth/login');
    
            $this->load->view('front/main', $data);
    
    }
    public function votedpm()
    {
        // Security check if the user is authorize
      

        // Get All Kandidat
        $kandidat_data_dpm = $this->Dpm_model->get_all('nourut', 'kandidatdpm', 'ASC');

        $data = array(

            // Data kandidat diambil dari database
            'kandidat_data_dpm' => $kandidat_data_dpm,

        );

        // Check status sudah memilih atau belum
        $status = $this->session->userdata('statusdpm');
        if ($status === 'Belum Memilih') {
            $this->load->view('front/votedpm', $data);
        } elseif ($status === 'Sudah Memilih') {

            $data = array(
                'nama' => $this->session->userdata('nama'),
            );

            $this->load->view('front/terimakasih', $data);
        }
    }

    public function doVotedpm($idkandidatdpm)
    {
        // Security check if the user is authorize
        if (!cek_login_bol()) {
            redirect('user/Userauth', 'refresh');
        }

        // menetapkan idpemilih
        $idpemilih = $this->session->userdata('userid');
        // Tipe pemilih apakah guru atau siswa
        $tipe = $this->session->userdata('level');

        // Check status sudah memilih atau belum
        $status = $this->session->userdata('statusdpm');
        if ($status === 'Belum Memilih') {

            // insertData
            $insertData = array(
                'tipe' => $tipe,
                'idpemilih' => $idpemilih,
                'idkandidatdpm' => $idkandidatdpm,
            );

            // Insert data
            $this->Dpm_model->insert('data_pemilihan_dpm', $insertData);

            // Update Session data
            $userData = array(
                'statusdpm' => 'Sudah Memilih'
            );
            $this->session->set_userdata($userData);

            // Update Database data
            $updateData = array(
                'statusdpm' => 'Sudah Memilih'
            );
            $this->Dpm_model->update('id', $idpemilih, 'data_pemilih', $updateData);

            // Menghitung jumlah perolehan suara
            $kandidatData = $this->Dpm_model->get_all('nourut', 'kandidatdpm', 'DESC');
            foreach ($kandidatData as $row) {
                // Berdasarkan idkandidat yang ada
                $jumlahSuara = $this->Dpm_model->tampil_data('idkandidatdpm', $row->$idkandidatdpm, 'data_pemilihan_dpm');
                $suaraData = array(
                    'jumlahsuara' => $jumlahSuara,
                );
                // Update jumlah suara counter ke database
                $this->Dpm_model->update('idkandidatdpm', $row->idkandidatdpm, 'kandidatdpm', $suaraData);
            }
            ;

            redirect('votedpm', 'refresh');
        } else {
            redirect('dpmvote', 'refresh');
            $this->session->set_flashdata(
                'message',
                '<div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                Anda sudah memilih </div>'
            );
        }
    }
} ?>