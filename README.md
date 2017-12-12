![logo](https://avatars3.githubusercontent.com/u/34305306?s=200&v=4"Logo")
# ecomplus-neo4j
PHP scripts to sync Neo4j (recommendations DB) with E-Com Plus API [[1]]
# Summary
1. [Introduction](https://github.com/ecomclub/ecomplus-neo4j#introduction)
   * [Requirements](https://github.com/ecomclub/ecomplus-neo4j#requirements)
   * [Installing dependency manager for PHP](https://github.com/ecomclub/ecomplus-neo4j#installing-dependency-manager-for-php)
   * [Dependency installation](https://github.com/ecomclub/ecomplus-neo4j#dependency-installation)
2. [Settings and basic usage](https://github.com/ecomclub/ecomplus-neo4j#settings-and-basic-usage)
   * [Neo4j Settings](https://github.com/ecomclub/ecomplus-neo4j#neo4j-settings)
   * [Basic use](https://github.com/ecomclub/ecomplus-neo4j#basic-use)
     * [Installing the Neo4j library in php](https://github.com/ecomclub/ecomplus-neo4j#installing-the-neo4j-library-in-php)
3. [Reference](https://github.com/ecomclub/ecomplus-neo4j#reference)

# Introduction
[Neo4j](https://neo4j.com/) is a non-relational graph-oriented database and is very common in social networks.
The idea of using Neo4j in the E-Com Plus API is to use the recommendations system that is widely used in social networks, where in this specific case when choosing a product the API will recommend another product in which it has the highest number of relations with the chosen product.

The scripts present in this repository are used to get all the public API information and synchronize them with the DB in order to create a product recommendation system.
## Requirements
* [PHP](http://www.php.net/) 7
* Neo4j Database (version 3.3)
## Installing dependency manager for PHP
For more information on how to install the manager [[2]]
## Dependency installation
For more information on installing the dependency required for this project [[3]]
# Settings and basic usage
## Neo4j Settings
To initialize Neo4j it is recommended that you create a new user in the DB and remove the default user, for this procedure, you use the following command lines[[4]]:
`$cypher-shell`

username:`neo4j`

password: `neo4j`

By default Neo4j is required to change the default user password:
``` cypher-shell
> CALL dbms.security.changePassword('newpassword');
```
Here in this example changed to * new password *.

And only after changing the default user password that it is possible to add the new user.
``` cypher-shell
> CALL dbms.security.createUser('user', 'password', 'false');
```
**_ Note:_** _requirePasswordChange_: this is optional, with a default of `true`. If this is true, (i) the user will be forced to change their password when they first log in, and (ii) until the user has changed their password, they will be prohibited from performing any other operation. [[5]]. In the example, the _requirePasswordChange_ is `false`, since it will no longer be necessary to change the password for this user.

After the new user is created it is necessary to _logout_:
```
>:exit
```
_Login_ with the new user:
```
$cypher-shell  -u user -p password
```
And delete the default user:
```cypher-shell
> CALL dbms.security.deleteUser('neo4j');
```
## Basic use
After the requirements and dependencies have been installed, the settings are made in Neo4j.
#### Installing the Neo4j library in php
Adding library to php dependencies
```
$composer require neoxygen/neoclient
```
In this project a script was created only for the manipulation of the data with Neo4j (neo4j.php). And to connect the php to Neo4j it is necessary to add the settings in the script, as follows[[3]]:
```php
neo4j.php
<?php
require_once 'vendor/autoload.php';
use Neoxygen\NeoClient\ClientBuilder;// add libary Neo4J
$user = 'user'; // DB User
$password = 'password'; //User Password
$client = ClientBuilder::create() // create connection Neo4j
    ->addConnection('default', 'http', 'localhost', 7474, true, $user, $password ) // initial connection
    ->build();
```
# Reference
[[1]] <https://ecomstore.docs.apiary.io/#>

[[2]] <https://getcomposer.org/download/>

[[3]] <https://github.com/neoxygen/neo4j-neoclient>

[[4]] <https://neo4j.com/docs/operations-manual/current/reference/user-management-community-edition/>

[[5]] <https://neo4j.com/docs/operations-manual/current/reference/user-management-community-edition/#userauth-add-user-ce>

[1]: https://github.com/ecomclub/ecomplus-neo4j#reference
[2]: https://github.com/ecomclub/ecomplus-neo4j#reference
[3]: https://github.com/ecomclub/ecomplus-neo4j#reference
[4]: https://github.com/ecomclub/ecomplus-neo4j#reference
[5]: https://github.com/ecomclub/ecomplus-neo4j#reference
