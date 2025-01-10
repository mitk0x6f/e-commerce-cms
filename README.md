![WIP](https://img.shields.io/badge/Work_In_Progress-yellow)
![Version](https://img.shields.io/badge/Version-0.2.2-blue)
[![Font Awesome](https://img.shields.io/badge/Font_Awesome-6-blue?logo=fontawesome&logoColor=yellow)](https://fontawesome.com/)
[![Lipis' flag-icons](https://img.shields.io/badge/Lipis'_flag--icons-7-green)](https://github.com/lipis/flag-icons)
[![noUiSlider](https://img.shields.io/badge/noUiSlider-15-blue)](https://refreshless.com/nouislider/)
[![Flickity](https://img.shields.io/badge/Flickity-2-yellow)](https://flickity.metafizzy.co/)
[![SCEditor](https://img.shields.io/badge/SCEditor-3-white)](https://www.sceditor.com/)  
---
![HTML](https://img.shields.io/static/v1?logo=html5&logoColor=E34F26&label=%20&labelColor=white&color=E34F26&message=HTML)
![CSS](https://img.shields.io/static/v1?logo=CSS&logoColor=663399&label=%20&labelColor=white&color=663399&message=CSS)
![JavaScript](https://img.shields.io/static/v1?logo=JavaScript&logoColor=F7DF1E&label=%20&labelColor=grey&color=F7DF1E&message=JavaScript)
![PHP](https://img.shields.io/static/v1?logo=PHP&logoColor=777BB4&label=%20&labelColor=white&color=777BB4&message=PHP)
![MySQL](https://img.shields.io/static/v1?logo=MySQL&logoColor=4479A1&label=%20&labelColor=white&color=4479A1&message=MySQL)  

---

# E-commerce CMS  
> Lightweight E-commerce CMS.  
> Live demo of version 0.1.1 [_here_](http://dimitar.freesite.online/).  

## Table of Contents  
* [General Info](#general-information)  
* [Features](#features)  
* [Screenshots](#screenshots)  
* [Setup](#setup)  
* [Usage](#usage)  
* [Room for Improvement](#room-for-improvement)  
* [License](#license)  


## General Information  
This project simplifies the creation of e-commerce websites. It enables individual shops to showcase their products directly to customers or allows businesses to host third-party sellers who can register and list their items. By doing so, it creates a unified online marketplace that connects sellers and customers on a single platform.  


## Features  
_Including WIP and future features_  
- Post / Edit / View articles.  
- Make / View purchases and purchase history.  
- Create / Use promo codes.  
- View data analytics.  
- Advertise new products.  
- Write / view product reviews.  
- Change display language.  
- Change display currency.  
- Send / receive instant messages.  


## Screenshots  
![Example screenshot 1](./README/screenshot1.png)  
![Example screenshot 2](./README/screenshot2.png)  
![Example screenshot 3](./README/screenshot3.png)  


## Setup  
#### Project requirements / dependencies  
- Hosting that supports:  
  - [PHP](https://www.php.net/)  
  - [.htaccess configuration file](https://httpd.apache.org/docs/2.4/howto/htaccess.html)  
  - Database of choice:  
    - [Cubrid](https://www.cubrid.org/)  
    - [FreeTDS](https://www.freetds.org/)  
    - [Firebird](https://www.firebirdsql.org/)  
    - [IBM DB2](https://www.ibm.com/db2)  
    - [IBM Informix Dynamic Server](https://www.ibm.com/products/informix)  
    - [MySQL 3.x/4.x/5.x/8.x](https://www.mysql.com/)  
    - [Oracle Call Interface](https://www.oracle.com/database/technologies/appdev/oci.html)  
    - [ODBC v3](https://learn.microsoft.com/en-us/sql/connect/odbc/microsoft-odbc-driver-for-sql-server?view=sql-server-ver16)  
    - [PostgreSQL](https://www.postgresql.org/)  
    - [SQLite 2 and 3](https://www.sqlite.org/)  
    - [Microsoft SQL Server](https://www.microsoft.com/en-us/sql-server/) / [SQL Azure](https://azure.microsoft.com/en-us/products/azure-sql/database)  
- [Font Awesome Free](https://fontawesome.com/)  
- [Lipis' flag-icons](https://github.com/lipis/flag-icons)  
- [noUiSlider](https://refreshless.com/nouislider/)  
- [Flickity 2](https://flickity.metafizzy.co/)  
- [SCEditor](https://www.sceditor.com/)  

#### Step 1  
Get a license!  

#### Step 2  
Clone the repository.  

```Bash
git clone https://github.com/mitk0x6f/e-commerce-cms.git
```  

#### Step 3  
Edit the configuration file.  
_Currently there is no automatic setup yet_  

`/templates/configuration.php`  

#### Step 4  
Sign up the first user.  

#### Step 5  
Edit the database manually to flip the account status to shop (seller). (_Because there is no automatic setup yet_)  


## Usage  
#### Template blocks  

> Including a template file  

`{{ INCLUDE file.html # }}`  

> Session data  

`{{ VAR USER.variable ~ }}`  

> Variable containing string or array  

`{{ VAR variable ~ }}`  

> Translation  

`{{ VAR translate.variable ~ }}`  

> Array element  

`{{ VAR variable1.variable2 ~ }}`  

> Looping an array  

`{{ FOR item in array_variable * }}`  

> Looping a nested array  

`{{ FOR item in array_variable.array * }}`  

> Element from loop  

`{{* key *}}`  

> Element from loop with translation  

`{{* translate.key *}}`  

> Conditions  

`{{ IF condition ? }}`  

> Comments  

`{{ -- Comment -- }}`  
```
{{ -- |
    Multi
    Line
    Comment
| -- }}
```  

#### Example index.php  

```php
<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Add cars data

$cars = [
    ["brand" => "BMW", "model" => "3-series"],
    ["brand" => "BMW", "model" => "5-series"],
    ["brand" => "BMW", "model" => "7-series"]
];

Builder::add_data("cars_list", $cars);

// Render page

Builder::page("home");
?>
```  

#### Example home.html  

```html
{{ INCLUDE header # }}

{{ IF User::get("signed_in") ? }}
    {{ FOR car in cars_list * }}
        {{ -- |
            We are translating the words brand and model,
            not the content of the variables.
        | -- }}
        
        {{ VAR translate.brand ~ }}: {{* brand *}}<br>
        {{ VAR translate.model ~ }}: {{* model *}}<hr>
    {{ END FOR }}
{{ ELSE }}
    Sign in to see the cars.
{{ END IF }}

{{ INCLUDE footer # }}
```  


## Room for Improvement  
To do:   
- Create administrator panel.  
- Make / View purchases.  
- View purchase history.  
- Use promo codes.  
- Create promo codes.  
- View data analytics.  
- Advertise new products from the administrator panel.  
- Write / view product reviews.  
- Change display currency.  
- Send / receive instant messages.  
- Move all TODO comments from the code here.  


## License  
This project is available under a paid license.  
For details, please see [LICENSE.md](LICENSE.md).  
