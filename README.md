## Skynet PHP SDK
This SDK is a community-created, unofficial SDK in PHP for the Skynet Decentralized Internet Network. It is taken as primarily a port from https://github.com/SkynetLabs/skynet-js.

Better documentation will come in the future. However, despite being a mirror, there are some details to know to use the library. The API will also likely improve and change in the future. This initial version is a reflection of the JS library.


### Classes
* Skynet
    * This is the bottom layer of the system where most operations happen at
* Registry
    * This is the basic key -> value store system. This is the database system that is used with your keypair to manage records
* Db
    * This is the system that is used to set basic registry entries linked to your keypair, which are not encrypted
* MySky
    * This is where all file management actions revolving around your account (seed) happen, including encrypted files. This also enables creating file paths that are stored as hashes in the Db and can also be encrypted via libsodium.

#### Functions

Most functions from skynet-js have been ported over. The ones of most interest will be in `src/functions/mysky.php`. Not all functions are grouped together the same way.

* generateSeedPhrase
* validatePhrase

Please note there are two versions of `genKeyPairFromSeed` in different namespaces since the `mysky` library uses a different version only for signing registry entries and getting your `PUBLIC ID`. `\Skynet\functions\mysky\genKeyPairFromSeed` uses an extra derivation hashing step for security.

It was decided given the evolution of PHP; these functions don't need to emulate PHP classes. So they are standalone functions that are inner dependant unless there is a reason to refactor.

### Usage

You will most often want to make use of the `MySky` class. Example:

***DO NOT USE THIS SEED***. It is valid for demonstration purposes, but as it is published here, it can never be trusted since *ANYONE* can make use or abuse it. Portal can be configured but will default to `siasky.net`

### Upload File
This example will upload a file and, by being logged in, will be pinned to your account:

```php
$drive = new \Skynet\MySky('roster donuts tycoon dunes muffin vector nasty jingle goblet amidst often wife digit earth eight');
$drive->setPortalLogin('john.doe@gmail.com', 'password');
$drive->setPortal('https://yourprivateportal.com');
$skyfile = $drive->getSkynet()->uploadFile(\Skynet\Types\File::fromResource('/mnt/data/file_to_upload.txt'));
echo $skyfile->getSkylink();

```
You can send raw data as well as long as it doesn't match a file:
```php
$skyfile = $drive->getSkynet()->uploadFile(\Skynet\Types\File::fromResource('Hello World'));
```

### Pin Skylink
This will pin a skylink to your account. A skylink must be pinned by atleast one portal to stay online. The skylink is a content ID including metadata:
```php
$skynet = new \Skynet\Skynet();
$drive->setPortalLogin('john.doe@gmail.com', 'password');
$skyfile = $drive->pinSkylink('XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg')
echo $skyfile->getSkylink();
```

### Create Plaintext DataLink/Resolver
```php
$drive = new \Skynet\MySky('roster donuts tycoon dunes muffin vector nasty jingle goblet amidst often wife digit earth eight');  
$drive->setPortalLogin('john.doe@gmail.com', 'password');  
$drive->getDb()->setDataLink($drive->getKey()->getPrivatekey(), 'hello_world', 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg');  
$entry = $drive->getDb()->getRegistry()->getEntry( $drive->getKey()->getPublicKey(), 'hello_world');
```
### Create JSON file
JSON data can be either a stdClass object or an array. the returned datalink is the skylink. It can be referenced again from the file path.
```php
$drive = new \Skynet\MySky('roster donuts tycoon dunes muffin vector nasty jingle goblet amidst often wife digit earth eight');  
$drive->setPortalLogin('john.doe@gmail.com', 'password');
$skylink = $drive->setJSON('/data/my_json_file.json', ['message' => 'hello world'] );
echo $skylink->getDataLink();
```
## Contributing

Any contributions are welcome. I will rapidly be iterating on this software since running as a server-side language can be very different from the browser. Semver will be followed as practical. Consider this software tested because it has nearly all unit tests from the JS version, but still experimental as it has not gotten any real-world use yet, but that will change.
