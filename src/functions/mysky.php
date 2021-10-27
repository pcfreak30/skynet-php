<?php

namespace Skynet\functions\mysky;

use Exception;
use Skynet\MySky;
use Skynet\Types\DerivationPathObject;
use Skynet\Types\KeyPair;
use Skynet\Uint8Array;
use function Skynet\functions\encrypted_files\sha512;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\strings\trimSuffix;
use function Skynet\functions\validation\validateHexString;
use function Skynet\functions\strings\startsWith;
use function Sodium\crypto_sign_publickey;
use function Sodium\crypto_sign_secretkey;
use function Sodium\crypto_sign_seed_keypair;
use const Skynet\CHECKSUM_WORDS_LENGTH;
use const Skynet\PHRASE_LENGTH;
use const Skynet\SALT_ROOT_DISCOVERABLE_KEY;
use const Skynet\SEED_LENGTH;
use const Skynet\SEED_WORDS_LENGTH;

/**
 *
 */
const DICTIONARY = [
	"abbey",
	"ablaze",
	"abort",
	"absorb",
	"abyss",
	"aces",
	"aching",
	"acidic",
	"across",
	"acumen",
	"adapt",
	"adept",
	"adjust",
	"adopt",
	"adult",
	"aerial",
	"afar",
	"affair",
	"afield",
	"afloat",
	"afoot",
	"afraid",
	"after",
	"agenda",
	"agile",
	"aglow",
	"agony",
	"agreed",
	"ahead",
	"aided",
	"aisle",
	"ajar",
	"akin",
	"alarms",
	"album",
	"alerts",
	"alley",
	"almost",
	"aloof",
	"alpine",
	"also",
	"alumni",
	"always",
	"amaze",
	"ambush",
	"amidst",
	"ammo",
	"among",
	"amply",
	"amused",
	"anchor",
	"angled",
	"ankle",
	"antics",
	"anvil",
	"apart",
	"apex",
	"aphid",
	"aplomb",
	"apply",
	"archer",
	"ardent",
	"arena",
	"argue",
	"arises",
	"army",
	"around",
	"arrow",
	"ascend",
	"aside",
	"asked",
	"asleep",
	"aspire",
	"asylum",
	"atlas",
	"atom",
	"atrium",
	"attire",
	"auburn",
	"audio",
	"august",
	"aunt",
	"autumn",
	"avatar",
	"avidly",
	"avoid",
	"awful",
	"awning",
	"awoken",
	"axes",
	"axis",
	"axle",
	"aztec",
	"azure",
	"baby",
	"bacon",
	"badge",
	"bailed",
	"bakery",
	"bamboo",
	"banjo",
	"basin",
	"batch",
	"bawled",
	"bays",
	"beer",
	"befit",
	"begun",
	"behind",
	"being",
	"below",
	"bested",
	"bevel",
	"beware",
	"beyond",
	"bias",
	"bids",
	"bikini",
	"birth",
	"bite",
	"blip",
	"boat",
	"bodies",
	"bogeys",
	"boil",
	"boldly",
	"bomb",
	"border",
	"boss",
	"both",
	"bovine",
	"boxes",
	"broken",
	"brunt",
	"bubble",
	"budget",
	"buffet",
	"bugs",
	"bulb",
	"bumper",
	"bunch",
	"butter",
	"buying",
	"buzzer",
	"byline",
	"bypass",
	"cabin",
	"cactus",
	"cadets",
	"cafe",
	"cage",
	"cajun",
	"cake",
	"camp",
	"candy",
	"casket",
	"catch",
	"cause",
	"cease",
	"cedar",
	"cell",
	"cement",
	"cent",
	"chrome",
	"cider",
	"cigar",
	"cinema",
	"circle",
	"claim",
	"click",
	"clue",
	"coal",
	"cobra",
	"cocoa",
	"code",
	"coffee",
	"cogs",
	"coils",
	"colony",
	"comb",
	"cool",
	"copy",
	"cousin",
	"cowl",
	"cube",
	"cuffs",
	"custom",
	"dads",
	"daft",
	"dagger",
	"daily",
	"damp",
	"dapper",
	"darted",
	"dash",
	"dating",
	"dawn",
	"dazed",
	"debut",
	"decay",
	"deftly",
	"deity",
	"dented",
	"depth",
	"desk",
	"devoid",
	"dice",
	"diet",
	"digit",
	"dilute",
	"dime",
	"dinner",
	"diode",
	"ditch",
	"divers",
	"dizzy",
	"doctor",
	"dodge",
	"does",
	"dogs",
	"doing",
	"donuts",
	"dosage",
	"dotted",
	"double",
	"dove",
	"down",
	"dozen",
	"dreams",
	"drinks",
	"drunk",
	"drying",
	"dual",
	"dubbed",
	"dude",
	"duets",
	"duke",
	"dummy",
	"dunes",
	"duplex",
	"dusted",
	"duties",
	"dwarf",
	"dwelt",
	"dying",
	"each",
	"eagle",
	"earth",
	"easy",
	"eating",
	"echo",
	"eden",
	"edgy",
	"edited",
	"eels",
	"eggs",
	"eight",
	"either",
	"eject",
	"elapse",
	"elbow",
	"eldest",
	"eleven",
	"elite",
	"elope",
	"else",
	"eluded",
	"emails",
	"ember",
	"emerge",
	"emit",
	"empty",
	"energy",
	"enigma",
	"enjoy",
	"enlist",
	"enmity",
	"enough",
	"ensign",
	"envy",
	"epoxy",
	"equip",
	"erase",
	"error",
	"estate",
	"etched",
	"ethics",
	"excess",
	"exhale",
	"exit",
	"exotic",
	"extra",
	"exult",
	"fading",
	"faked",
	"fall",
	"family",
	"fancy",
	"fatal",
	"faulty",
	"fawns",
	"faxed",
	"fazed",
	"feast",
	"feel",
	"feline",
	"fences",
	"ferry",
	"fever",
	"fewest",
	"fiat",
	"fibula",
	"fidget",
	"fierce",
	"fight",
	"films",
	"firm",
	"five",
	"fixate",
	"fizzle",
	"fleet",
	"flying",
	"foamy",
	"focus",
	"foes",
	"foggy",
	"foiled",
	"fonts",
	"fossil",
	"fowls",
	"foxes",
	"foyer",
	"framed",
	"frown",
	"fruit",
	"frying",
	"fudge",
	"fuel",
	"fully",
	"fuming",
	"fungal",
	"future",
	"fuzzy",
	"gables",
	"gadget",
	"gags",
	"gained",
	"galaxy",
	"gambit",
	"gang",
	"gasp",
	"gather",
	"gauze",
	"gave",
	"gawk",
	"gaze",
	"gecko",
	"geek",
	"gels",
	"germs",
	"geyser",
	"ghetto",
	"ghost",
	"giant",
	"giddy",
	"gifts",
	"gills",
	"ginger",
	"girth",
	"giving",
	"glass",
	"glide",
	"gnaw",
	"gnome",
	"goat",
	"goblet",
	"goes",
	"going",
	"gone",
	"gopher",
	"gossip",
	"gotten",
	"gown",
	"grunt",
	"guest",
	"guide",
	"gulp",
	"guru",
	"gusts",
	"gutter",
	"guys",
	"gypsy",
	"gyrate",
	"hairy",
	"having",
	"hawk",
	"hazard",
	"heels",
	"hefty",
	"height",
	"hence",
	"heron",
	"hiding",
	"hijack",
	"hiker",
	"hills",
	"hinder",
	"hippo",
	"hire",
	"hive",
	"hoax",
	"hobby",
	"hockey",
	"hold",
	"honked",
	"hookup",
	"hope",
	"hornet",
	"hotel",
	"hover",
	"howls",
	"huddle",
	"huge",
	"hull",
	"humid",
	"hunter",
	"huts",
	"hybrid",
	"hyper",
	"icing",
	"icon",
	"idiom",
	"idled",
	"idols",
	"igloo",
	"ignore",
	"iguana",
	"impel",
	"incur",
	"injury",
	"inline",
	"inmate",
	"input",
	"insult",
	"invoke",
	"ionic",
	"irate",
	"iris",
	"irony",
	"island",
	"issued",
	"itches",
	"items",
	"itself",
	"ivory",
	"jabbed",
	"jaded",
	"jagged",
	"jailed",
	"jargon",
	"jaunt",
	"jaws",
	"jazz",
	"jeans",
	"jeers",
	"jester",
	"jewels",
	"jigsaw",
	"jingle",
	"jive",
	"jobs",
	"jockey",
	"jogger",
	"joking",
	"jolted",
	"jostle",
	"joyous",
	"judge",
	"juicy",
	"july",
	"jump",
	"junk",
	"jury",
	"karate",
	"keep",
	"kennel",
	"kept",
	"kettle",
	"king",
	"kiosk",
	"kisses",
	"kiwi",
	"knee",
	"knife",
	"koala",
	"ladder",
	"lagoon",
	"lair",
	"lakes",
	"lamb",
	"laptop",
	"large",
	"last",
	"later",
	"lava",
	"layout",
	"lazy",
	"ledge",
	"leech",
	"left",
	"legion",
	"lemon",
	"lesson",
	"liar",
	"licks",
	"lids",
	"lied",
	"light",
	"lilac",
	"limits",
	"linen",
	"lion",
	"liquid",
	"listen",
	"lively",
	"loaded",
	"locker",
	"lodge",
	"lofty",
	"logic",
	"long",
	"lopped",
	"losing",
	"loudly",
	"love",
	"lower",
	"loyal",
	"lucky",
	"lumber",
	"lunar",
	"lurk",
	"lush",
	"luxury",
	"lymph",
	"lynx",
	"lyrics",
	"macro",
	"mailed",
	"major",
	"makeup",
	"malady",
	"mammal",
	"maps",
	"match",
	"maul",
	"mayor",
	"maze",
	"meant",
	"memoir",
	"menu",
	"merger",
	"mesh",
	"metro",
	"mews",
	"mice",
	"midst",
	"mighty",
	"mime",
	"mirror",
	"misery",
	"moat",
	"mobile",
	"mocked",
	"mohawk",
	"molten",
	"moment",
	"money",
	"moon",
	"mops",
	"morsel",
	"mostly",
	"mouth",
	"mowing",
	"much",
	"muddy",
	"muffin",
	"mugged",
	"mullet",
	"mumble",
	"muppet",
	"mural",
	"muzzle",
	"myriad",
	"myth",
	"nagged",
	"nail",
	"names",
	"nanny",
	"napkin",
	"nasty",
	"navy",
	"nearby",
	"needed",
	"neon",
	"nephew",
	"nerves",
	"nestle",
	"never",
	"newt",
	"nexus",
	"nibs",
	"niche",
	"niece",
	"nifty",
	"nimbly",
	"nobody",
	"nodes",
	"noises",
	"nomad",
	"noted",
	"nouns",
	"nozzle",
	"nuance",
	"nudged",
	"nugget",
	"null",
	"number",
	"nuns",
	"nurse",
	"nylon",
	"oaks",
	"oars",
	"oasis",
	"object",
	"occur",
	"ocean",
	"odds",
	"offend",
	"often",
	"okay",
	"older",
	"olive",
	"omega",
	"onion",
	"online",
	"onto",
	"onward",
	"oozed",
	"opened",
	"opus",
	"orange",
	"orbit",
	"orchid",
	"orders",
	"organs",
	"origin",
	"oscar",
	"otter",
	"ouch",
	"ought",
	"ounce",
	"oust",
	"oval",
	"oven",
	"owed",
	"owls",
	"owner",
	"oxygen",
	"oyster",
	"ozone",
	"pact",
	"pager",
	"palace",
	"paper",
	"pastry",
	"patio",
	"pause",
	"peeled",
	"pegs",
	"pencil",
	"people",
	"pepper",
	"pests",
	"petals",
	"phase",
	"phone",
	"piano",
	"picked",
	"pierce",
	"pimple",
	"pirate",
	"pivot",
	"pixels",
	"pizza",
	"pledge",
	"pliers",
	"plus",
	"poetry",
	"point",
	"poker",
	"polar",
	"ponies",
	"pool",
	"potato",
	"pouch",
	"powder",
	"pram",
	"pride",
	"pruned",
	"prying",
	"public",
	"puck",
	"puddle",
	"puffin",
	"pulp",
	"punch",
	"puppy",
	"purged",
	"push",
	"putty",
	"pylons",
	"python",
	"queen",
	"quick",
	"quote",
	"radar",
	"rafts",
	"rage",
	"raking",
	"rally",
	"ramped",
	"rapid",
	"rarest",
	"rash",
	"rated",
	"ravine",
	"rays",
	"razor",
	"react",
	"rebel",
	"recipe",
	"reduce",
	"reef",
	"refer",
	"reheat",
	"relic",
	"remedy",
	"repent",
	"reruns",
	"rest",
	"return",
	"revamp",
	"rewind",
	"rhino",
	"rhythm",
	"ribbon",
	"richly",
	"ridges",
	"rift",
	"rigid",
	"rims",
	"riots",
	"ripped",
	"rising",
	"ritual",
	"river",
	"roared",
	"robot",
	"rodent",
	"rogue",
	"roles",
	"roomy",
	"roped",
	"roster",
	"rotate",
	"rover",
	"royal",
	"ruby",
	"rudely",
	"rugged",
	"ruined",
	"ruling",
	"rumble",
	"runway",
	"rural",
	"sack",
	"safety",
	"saga",
	"sailor",
	"sake",
	"salads",
	"sample",
	"sanity",
	"sash",
	"satin",
	"saved",
	"scenic",
	"school",
	"scoop",
	"scrub",
	"scuba",
	"second",
	"sedan",
	"seeded",
	"setup",
	"sewage",
	"sieve",
	"silk",
	"sipped",
	"siren",
	"sizes",
	"skater",
	"skew",
	"skulls",
	"slid",
	"slower",
	"slug",
	"smash",
	"smog",
	"snake",
	"sneeze",
	"sniff",
	"snout",
	"snug",
	"soapy",
	"sober",
	"soccer",
	"soda",
	"soggy",
	"soil",
	"solved",
	"sonic",
	"soothe",
	"sorry",
	"sowed",
	"soya",
	"space",
	"speedy",
	"sphere",
	"spout",
	"sprig",
	"spud",
	"spying",
	"square",
	"stick",
	"subtly",
	"suede",
	"sugar",
	"summon",
	"sunken",
	"surfer",
	"sushi",
	"suture",
	"swept",
	"sword",
	"swung",
	"system",
	"taboo",
	"tacit",
	"tagged",
	"tail",
	"taken",
	"talent",
	"tamper",
	"tanks",
	"tasked",
	"tattoo",
	"taunts",
	"tavern",
	"tawny",
	"taxi",
	"tell",
	"tender",
	"tepid",
	"tether",
	"thaw",
	"thorn",
	"thumbs",
	"thwart",
	"ticket",
	"tidy",
	"tiers",
	"tiger",
	"tilt",
	"timber",
	"tinted",
	"tipsy",
	"tirade",
	"tissue",
	"titans",
	"today",
	"toffee",
	"toilet",
	"token",
	"tonic",
	"topic",
	"torch",
	"tossed",
	"total",
	"touchy",
	"towel",
	"toxic",
	"toyed",
	"trash",
	"trendy",
	"tribal",
	"truth",
	"trying",
	"tubes",
	"tucks",
	"tudor",
	"tufts",
	"tugs",
	"tulips",
	"tunnel",
	"turnip",
	"tusks",
	"tutor",
	"tuxedo",
	"twang",
	"twice",
	"tycoon",
	"typist",
	"tyrant",
	"ugly",
	"ulcers",
	"umpire",
	"uncle",
	"under",
	"uneven",
	"unfit",
	"union",
	"unmask",
	"unrest",
	"unsafe",
	"until",
	"unveil",
	"unwind",
	"unzip",
	"upbeat",
	"update",
	"uphill",
	"upkeep",
	"upload",
	"upon",
	"upper",
	"urban",
	"urgent",
	"usage",
	"useful",
	"usher",
	"using",
	"usual",
	"utmost",
	"utopia",
	"vague",
	"vain",
	"value",
	"vane",
	"vary",
	"vats",
	"vaults",
	"vector",
	"veered",
	"vegan",
	"vein",
	"velvet",
	"vessel",
	"vexed",
	"vials",
	"victim",
	"video",
	"viking",
	"violin",
	"vipers",
	"vitals",
	"vivid",
	"vixen",
	"vocal",
	"vogue",
	"voice",
	"vortex",
	"voted",
	"vowels",
	"voyage",
	"wade",
	"waffle",
	"waist",
	"waking",
	"wanted",
	"warped",
	"water",
	"waxing",
	"wedge",
	"weird",
	"went",
	"wept",
	"were",
	"whale",
	"when",
	"whole",
	"width",
	"wield",
	"wife",
	"wiggle",
	"wildly",
	"winter",
	"wiring",
	"wise",
	"wives",
	"wizard",
	"wobbly",
	"woes",
	"woken",
	"wolf",
	"woozy",
	"worry",
	"woven",
	"wrap",
	"wrist",
	"wrong",
	"yacht",
	"yahoo",
	"yanks",
];


/**
 * @param string $pathSeed
 * @param string $subPath
 * @param bool   $isDirectory
 *
 * @return string
 * @throws \SodiumException
 */
function deriveEncryptedFileSeed( string $pathSeed, string $subPath, bool $isDirectory ): string {
	validateHexString( 'pathSeed', $pathSeed, 'parameter' );

	$pathSeedBytes = hexToUint8Array( $pathSeed );
	$sanitizedPath = sanitizePath( $subPath );

	if ( null === $sanitizedPath ) {
		throw  new Exception( sprintf( "Input subPath '%s' not a valid path", $subPath ) );
	}

	$names = explode( '/', $sanitizedPath );

	$namesCount = count( $names );
	foreach ( $names as $index => $name ) {
		$directory         = $index === $namesCount - 1 ? $isDirectory : true;
		$derivationPathObj = new DerivationPathObject( [
			'pathSeed'  => $pathSeedBytes,
			'directory' => $directory,
			'name'      => $name,
		] );

		$derivationPath = hashDerivationPathObject( $derivationPathObj );
		$bytes          = Uint8Array::from( sha512( MySky::SALT_ENCRYPTED_CHILD ) . $derivationPath );
		$pathSeedBytes  = Uint8Array::from( sha512( $bytes->toString() ) );
	}

	return toHexString( $pathSeedBytes->slice( 0, MySky::ENCRYPTION_PATH_SEED_LENGTH ) );
}

/**
 * @param string $path
 *
 * @return string|null
 */
function sanitizePath( string $path ): ?string {
	$path = trim( $path );

	if ( startsWith( $path, '/' ) ) {
		return null;
	}

	$path = trimSuffix( $path, '/' );
	$path = removeAdjacentChars( $path, '/' );

	$pathArray    = explode( '/', $path );
	$pathArray[0] = strtolower( $pathArray[0] );

	$sanitizedPath = implode( '/', $pathArray );
	if ( '' === $sanitizedPath ) {
		return null;
	}

	return $sanitizedPath;
}

/**
 * @param string $str
 * @param string $char
 *
 * @return string
 */
function removeAdjacentChars( string $str, string $char ): string {
	$pathArray = str_split( $str );

	for ( $i = 0; $i < count( $pathArray ) - 1; ) {
		if ( $char === $pathArray[ $i ] && $pathArray[ $i + 1 ] == $char ) {
			array_splice( $pathArray, $i, 1 );
			continue;
		}
		$i ++;
	}

	return implode( '', $pathArray );
}

/**
 * @param \Skynet\Types\DerivationPathObject $obj
 *
 * @return string
 */
function hashDerivationPathObject( DerivationPathObject $obj ): string {
	$data = $obj->getPathSeed() . ( (int) $obj->isDirectory() ) . $obj->getName();

	return sha512( $data );

}

/**
 * @return string
 * @throws \Exception
 */
function generateSeedPhrase(): string {
	$seedWords = new Uint8Array( SEED_WORDS_LENGTH );
	for ( $i = 0; $i < SEED_WORDS_LENGTH; $i ++ ) {
		$int     = random_int( 0, PHP_INT_MAX );
		$numBits = 10;

		if ( 12 === $i ) {
			$numBits = 8;
		}

		$int %= 1 << $numBits;

		$seedWords->set( $int, $i );
	}

	$checksumWords = generateChecksumWordsFromSeedWords( $seedWords );
	$phraseWords   = [];

	for ( $i = 0; $i < SEED_WORDS_LENGTH; $i ++ ) {
		$phraseWords[ $i ] = DICTIONARY[ $seedWords[ $i ] ];
	}
	for ( $i = 0; $i < CHECKSUM_WORDS_LENGTH; $i ++ ) {
		$phraseWords[ $i + SEED_WORDS_LENGTH ] = DICTIONARY[ $checksumWords[ $i ] ];
	}

	return implode( ' ', $phraseWords );

}

/**
 * @param \Skynet\Uint8Array $seedWords
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function generateChecksumWordsFromSeedWords( Uint8Array $seedWords ): Uint8Array {
	if ( $seedWords->getMaxLength() !== SEED_WORDS_LENGTH ) {
		throw new Exception( sprintf( 'Input seed was not of length %d', SEED_WORDS_LENGTH ) );
	}

	$seed = seedWordsToSeed( $seedWords );
	$h    = Uint8Array::from( sha512( $seed->toString() ) );

	return hashToChecksumWords( $h );
}

/**
 * @param \Skynet\Uint8Array $h
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function hashToChecksumWords( Uint8Array $h ): Uint8Array {
	$word1 = $h[0] << 8;
	$word1 += $h[1];
	$word1 >>= 6;

	$word2 = $h[1] << 10;
	$word2 &= 0xffff;
	$word2 += $h[2] << 2;
	$word2 >>= 6;

	return Uint8Array::from( [ $word1, $word2 ] );
}

/**
 * @param \Skynet\Uint8Array $seedWords
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function seedWordsToSeed( Uint8Array $seedWords ): Uint8Array {
	if ( $seedWords->getMaxLength() !== SEED_WORDS_LENGTH ) {
		throw new Exception( sprintf( 'Input seed was not of length %d, was %d', SEED_WORDS_LENGTH, $seedWords->getMaxLength() ) );
	}

	$bytes   = new Uint8Array( SEED_LENGTH );
	$curByte = 0;
	$curBit  = 0;

	for ( $i = 0; $i < SEED_WORDS_LENGTH; $i ++ ) {
		$word     = $seedWords[ $i ];
		$wordBits = 10;

		if ( $i === SEED_WORDS_LENGTH - 1 ) {
			$wordBits = 8;
		}

		for ( $j = 0; $j < $wordBits; $j ++ ) {
			$bitSet = ( $word & ( 1 << ( $wordBits - $j - 1 ) ) ) > 0;

			if ( $bitSet ) {
				$bytes[ $curByte ] |= 1 << ( 8 - $curBit - 1 );
			}

			$curBit ++;

			if ( $curBit >= 8 ) {
				$curByte ++;
				$curBit = 0;
			}
		}
	}

	return $bytes;
}

/**
 * @param string $phrase
 *
 * @return array
 * @throws \Exception
 */
function validatePhrase( string $phrase ) {
	$phrase = sanitizePhrase( $phrase );

	$phraseWords = explode( ' ', $phrase );
	if ( count( $phraseWords ) !== PHRASE_LENGTH ) {
		return [
			false,
			sprintf( "Phrase must be '%d' words long, was '%d'", PHRASE_LENGTH, count( $phraseWords ) ),
			null,
		];
	}

	$seedWords = new Uint8Array( SEED_WORDS_LENGTH );
	$i         = 0;

	foreach ( $phraseWords as $word ) {
		if ( 3 > strlen( $word ) ) {
			return [ false, sprintf( "Word %d is not at least 3 letters long", $i + 1 ), null ];
		}

		$prefix = substr( $word, 0, 3 );
		$bound  = count( DICTIONARY );
		if ( 12 === $i ) {
			$bound = 256;
		}

		$found = - 1;

		for ( $j = 0; $j < $bound; $j ++ ) {
			$curPrefix = substr( DICTIONARY[ $j ], 0, 3 );

			if ( $curPrefix === $prefix ) {
				$found = $j;
				break;
			}

			if ( $curPrefix > $prefix ) {
				break;
			}
		}

		if ( 0 > $found ) {
			if ( 12 === $i ) {
				return [
					false,
					sprintf( "Prefix for word %d must be found in the first 256 words of the dictionary", $i + 1 ),
					null,
				];
			}

			return [
				false,
				sprintf( 'Unrecognized prefix "%s" at word %d, not found in dictionary', $prefix, $i + 1 ),
				null,
			];
		}

		$seedWords[ $i ] = $found;
		$i ++;
	}

	$checksumWords = generateChecksumWordsFromSeedWords( $seedWords );

	for ( $i = 0; $i < CHECKSUM_WORDS_LENGTH; $i ++ ) {
		$prefix = substr( DICTIONARY[ $checksumWords[ $i ] ], 0, 3 );
		if ( substr( $phraseWords[ $i + SEED_WORDS_LENGTH ], 0, 3 ) !== $prefix ) {
			return [
				false,
				sprintf( "Word '%s' is not a valid checksum for the seed", $phraseWords[ $i + SEED_WORDS_LENGTH ] ),
				null,
			];
		}
	}

	return [ true, '', seedWordsToSeed( $seedWords ) ];
}

/**
 * @param string $phrase
 *
 * @return string
 */
function sanitizePhrase( string $phrase ): string {
	return removeAdjacentChars( strtolower( trim( $phrase ) ), ' ' );
}

/**
 * @param string $phrase
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function phraseToSeed( string $phrase ): Uint8Array {
	$phrase = sanitizePhrase( $phrase );

	[ $valid, $error, $seed ] = validatePhrase( $phrase );

	if ( ! $valid || ! $seed ) {
		throw new Exception( $error );
	}

	return $seed;
}

/**
 * @param \Skynet\Uint8Array $seed
 *
 * @return string
 * @throws \Exception
 */
function seedToPhrase( Uint8Array $seed ): string {
	$seedWords = seedToSeedWords( $seed );

	return seedWordsToPhrase( $seedWords );
}

/**
 * @param \Skynet\Uint8Array $seedWords
 *
 * @return string
 * @throws \Exception
 */
function seedWordsToPhrase( Uint8Array $seedWords ) {
	if ( $seedWords->getMaxLength() !== SEED_WORDS_LENGTH ) {
		throw new Exception( sprintf( 'Input seed was not of length %d, was %d', SEED_WORDS_LENGTH, $seedWords->getMaxLength() ) );
	}

	$phrase = [];

	$checksumWords         = generateChecksumWordsFromSeedWords( $seedWords );
	$seedWordsWithChecksum = [ ...$seedWords, ...$checksumWords ];

	$i = 0;
	foreach ( $seedWordsWithChecksum as $seedWord ) {
		$maxSeedWord = count( DICTIONARY );
		if ( 12 === $i ) {
			$maxSeedWord = 256;
		}

		if ( $seedWord > $maxSeedWord ) {
			throw new Exception( sprintf( "Seed word '%d' is greater than the max seed word '%d' for seed index '%d'", $seedWord, $maxSeedWord, $i ) );
		}

		$phrase[] = DICTIONARY[ $seedWord ];
		$i ++;
	}

	return implode( ' ', $phrase );
}

/**
 * @param \Skynet\Uint8Array $seed
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function seedToSeedWords( Uint8Array $seed ) {
	if ( $seed->getMaxLength() !== SEED_LENGTH ) {
		throw new Exception( sprintf( "Input seed should be length '%d', was '%d'", SEED_LENGTH, $seed->getMaxLength() ) );
	}

	$words    = new Uint8Array( SEED_WORDS_LENGTH );
	$curWord  = 0;
	$curBit   = 0;
	$wordBits = 10;

	for ( $i = 0; $i < SEED_LENGTH; $i ++ ) {
		$byte = $seed[ $i ];

		for ( $j = 0; $j < 8; $j ++ ) {
			$bitSet = ( $byte & ( 1 << ( 8 - $j - 1 ) ) ) > 0;

			if ( $bitSet ) {
				$words[ $curWord ] |= 1 << ( $wordBits - $curBit - 1 );
			}

			$curBit ++;

			if ( $curBit >= $wordBits ) {
				$curWord ++;
				$curBit = 0;

				if ( SEED_WORDS_LENGTH - 1 === $curWord ) {
					$wordBits = 8;
				}
			}
		}
	}

	return $words;
}

/**
 * @param \Skynet\Uint8Array $seed
 *
 * @return \Skynet\Types\KeyPair
 * @throws \SodiumException
 */
function genKeyPairFromSeed( string $seed ): KeyPair {
	$bytes     = sha512( SALT_ROOT_DISCOVERABLE_KEY ) . sha512( $seed );
	$hashBytes = substr( sha512( $bytes ), 0, 32 );

	$keyPair = crypto_sign_seed_keypair( $hashBytes );

	return new KeyPair(
		[
			'publicKey'  => toHexString( crypto_sign_publickey( $keyPair ) ),
			'privateKey' => toHexString( crypto_sign_secretkey( $keyPair ) ),
		] );
}
