# php-upload-class

Upload class for huge files. It was tested with a 35gb file. NOT OPTIMIZED IN FRONTEND.

NOTE: This class and README are being redone. Please, wait for more detailed information.

---

## How to use

- Add profile: 
  Set the Upload 'profile', for validation during upload proccess:

```php
include('class/path/file.php');

$profile = array(
        "types" => array("jpeg", "jpg", "png"), // which filetypes are accepted
        "folder" => "./uploads/", // which folder your files go, for this specific profile, on your local server
        "size" => 266000, // max size of file upload
        "total" => 10, // total files that can be upload on this input
        "vars" => array(), // additional vars that will be passed to frontend and backend
);

Upload::addProfile('image', $profile);
```

After that, bind the input name to the profile:

```php
Upload::set('image', 'image');
```

---

- Prepare your page: 
  Check this repo for further info: (js-upload-class)[https://github.com/Matheus2212/js-upload-class]

---

- Init: 
  After preparation, on your page's footer or end of the route, call:

```php
Upload::init() ;
```
