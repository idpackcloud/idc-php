[![idc-php](https://github.com/idpackcloud/idc-php/blob/main/examples/images/idpack_cloud.jpg)](https://github.com/idpackcloud/idc-php/blob/7e8be2e430e02a9dd8ed94dd5ae167f0b3b2b412/examples/images/idpack_cloud.jpg){:width="250px"}

# IDC PHP bindings

The IDpack Cloud PHP library provides convenient access to the IDC API from applications written in PHP. The API is available with an IDC Professional or IDC Enterprise plan.

Requirements
========
PHP 5.6.0 and later.

Not tested yet

* PHP 8.0
* PHP 8.1

Manual Setup
========
Include the `idc.class.php` file into your project and set it up like this:

```php
require_once 'idc.class.php';
$idc = new IDpack('username', 'password', 'user_secret_key', 'project_secret_key');
```
or
```php
require_once 'idc.class.php';
$idc = new IDpackCloud();
$idc->setUsername('username');
$idc->setPassword('password');
$idc->setUserSecretKey('user_secret_key');
$idc->setProjectSecretKey('project_secret_key');
```

Code Examples
========
### Grab a record from a project in a JSON format.

```php
require_once 'idc.class.php';
$idc = new IDpack('username', 'password', 'user_secret_key', 'project_secret_key');
$response = $idc->get_record(['idc_id_number' => '10000']);
echo $response;
```

### Response:
```json
{
  "status": "success",
  "message": null,
  "code": 200,
  "data": {
    "record": {
      "idc_id_number": "10000",
      "idc_active": "1",
      "idc_trash": "0",
      "idc_colorcode": "6",
      "idc_firstname": "David",
      "idc_lastname": "Wilson",
      "idc_mobilephone": null,
      "idc_email": "david@example.com",
      "idc_member_id": "163567",
      "idc_expirationdate": "2025-01-22",
      "idc_insdate": "2023-01-23 06:53:55",
      "idc_moddate": "2023-01-23 14:53:16",
      "idc_picturedate": "2023-01-29 18:12:39",
      "idc_printdate": "2023-01-29 13:26:20"
    }
  },
  "api": {
    "api_action": "get_record", 
    "api_queries_remaining": 8345,
    "api_software": "IDpack Cloud",
    "api_version": "2.0.001",
    "api_request_date": "2023-01-29 19:30:12"
  }
}
```

### Grab all records from a project in a JSON format.

```php
$response = $idc->get_all_records();
echo $response;
```

### Grab a jpeg Base64 Photo ID from a project record in a JSON format.

```php
$response = $idc->get_photo_id(['idc_id_number' => '10000'], 'jpeg');
echo $response;
```

### Grab a jpeg Base64 Badge Preview from a project record in a JSON format.

```php
$response = $idc->get_badge_preview(['idc_id_number' => '10000'], 'jpeg');
echo $response;
```

### Update a record from a project.

```php
$response = $idc->update_record(['idc_id_number' => '10000'], ['idc_firstname' => 'Julie', 'idc_lastname' => 'Smith']);
echo $response;
```

### Insert a record in a project.

```php
$response = $idc->insert_record(['idc_colorcode' => 2, 'idc_firstname' => 'Mark', 'idc_lastname' => 'Morgan']);
echo $response;

//get the inserted primary key
$insert_id = $idc->getInsertID();
```

### Delete a record in a project.

```php
$response = $idc->delete_record(['idc_id_number' => '10000']);
echo $response;
```
