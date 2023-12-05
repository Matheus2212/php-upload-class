<?php

if ($_POST) {
        if (!class_exists("Upload")) {
                include('UploadClass.php');
        }
        $return = array(
                'status' => false,
                'message' => '',
        );
        if (isset($_POST['upload'])) {
                $data = @json_decode($_POST['upload'], true);
                if ($data) {
                        //print_r($data);
                        if (!isset($data['profile']['name']) || !isset($data['profile']['key']) || (md5($data['profile']['name']) !== $data['profile']['key'])) {
                                $return['status'] = false;
                                $return['message'] = "Error. Upload integrity has been compromised. Upload failed.";
                        } else {
                                $profile = Upload::getProfile($data['profile']['name']);
                                if (!isset($data['fileNameSet']) || !$data['fileNameSet']) {
                                        $data['fileName'] = Upload::setNewName($data['fileName']);
                                }
                                if (Upload::saveFile($data['fileName'], $data['data'], $profile['config']['folder'])) {
                                        $return['status'] = true;
                                        $return['fileName'] = $data['fileName'];
                                        $return['fileNameSet'] = true;
                                        $return['message'] = "File Uploaded";
                                        if ($data["totalRequests"] == ($data["currentRequest"] + 1)) {
                                                Upload::unsetLog($data['fileName'], $profile['config']['folder']);
                                        }
                                } else {
                                        $return['message'] = "File weren't Uploaded";
                                }
                        }
                } else {
                        $return["message"] = "Error. No data has been received. Upload failed.";
                }
        }
        if (isset($_POST['cancel'])) {
                $data = @json_decode($_POST['cancel'], true);
                if ($data) {
                        $profile = Upload::getProfile($data['profile']['name']);
                        $path = $profile['config']["folder"] . DIRECTORY_SEPARATOR . $data["fileName"];
                        Upload::unsetLog($data['fileName'], $profile['config']['folder']);
                        if (Upload::delete($path)) {
                                $return["message"] = "Upload canceled.";
                                $return["status"] = true;
                        }
                }
        }
        if (isset($_POST['delete'])) {
                $data = @json_decode($_POST['delete'], true);
                if ($data) {
                        $profile = Upload::getProfile($data['profile']['name']);
                        $path = $profile['config']["folder"] . DIRECTORY_SEPARATOR . $data["fileName"];
                        if (Upload::delete($path)) {
                                $return["message"] = "Upload deleted.";
                                $return["status"] = true;
                        }
                }
        }
        if (isset($uploadReturn) && $uploadReturn) {
                $return['profile'] = $profile;
                return json_encode($return);
        }
        echo json_encode($return);
}
