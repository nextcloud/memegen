<?php

namespace OCA\Memegen\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Memegen\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;


class MemegenService {

	private LoggerInterface $logger;
	private IClient $client;
	private IConfig $config;
	private IL10N $l10n;
    private array $memeTemplates;

	public function __construct (LoggerInterface $logger,
								 IClientService  $clientService,
								 IConfig $config,
								 IL10N $l10n) {
		$this->client = $clientService->newClient();

		$this->logger = $logger;
		$this->config = $config;
		$this->l10n = $l10n;

        $this->memeTemplates = ["aag" => ["name" => "Ancient Aliens Guy","blank_url" => "https://api.memegen.link/images/aag.png","lines" => "2",],
        "ackbar" => ["name" => "It's A Trap!","blank_url" => "https://api.memegen.link/images/ackbar.png","lines" => "2",],
        "afraid" => ["name" => "Afraid to Ask Andy","blank_url" => "https://api.memegen.link/images/afraid.png","lines" => "2",],
        "agnes" => ["name" => "Agnes Harkness Winking","blank_url" => "https://api.memegen.link/images/agnes.png","lines" => "2",],
        "aint-got-time" => ["name" => "Sweet Brown","blank_url" => "https://api.memegen.link/images/aint-got-time.png","lines" => "2",],
        "ams" => ["name" => "Awkward Moment Seal","blank_url" => "https://api.memegen.link/images/ams.png","lines" => "2",],
        "ants" => ["name" => "Do You Want Ants?","blank_url" => "https://api.memegen.link/images/ants.png","lines" => "2",],
        "apcr" => ["name" => "Almost Politically Correct Redneck","blank_url" => "https://api.memegen.link/images/apcr.png","lines" => "2",],
        "astronaut" => ["name" => "Always Has Been","blank_url" => "https://api.memegen.link/images/astronaut.png","lines" => "4",],
        "atis" => ["name" => "And Then I Said","blank_url" => "https://api.memegen.link/images/atis.png","lines" => "2",],
        "away" => ["name" => "Life... Finds a Way","blank_url" => "https://api.memegen.link/images/away.png","lines" => "2",],
        "awesome" => ["name" => "Socially Awesome Penguin","blank_url" => "https://api.memegen.link/images/awesome.png","lines" => "2",],
        "awesome-awkward" => ["name" => "Socially Awesome Awkward Penguin","blank_url" => "https://api.memegen.link/images/awesome-awkward.png","lines" => "2",],
        "awkward" => ["name" => "Socially Awkward Penguin","blank_url" => "https://api.memegen.link/images/awkward.png","lines" => "2",],
        "awkward-awesome" => ["name" => "Socially Awkward Awesome Penguin","blank_url" => "https://api.memegen.link/images/awkward-awesome.png","lines" => "2",],
        "bad" => ["name" => "You Should Feel Bad","blank_url" => "https://api.memegen.link/images/bad.png","lines" => "2",],
        "badchoice" => ["name" => "Milk Was a Bad Choice","blank_url" => "https://api.memegen.link/images/badchoice.png","lines" => "2",],
        "bd" => ["name" => "Butthurt Dweller","blank_url" => "https://api.memegen.link/images/bd.png","lines" => "2",],
        "because" => ["name" => "Men in Black","blank_url" => "https://api.memegen.link/images/because.png","lines" => "2",],
        "bender" => ["name" => "I'm Going to Build My Own Theme Park","blank_url" => "https://api.memegen.link/images/bender.png","lines" => "2",],
        "bihw" => ["name" => "But It's Honest Work","blank_url" => "https://api.memegen.link/images/bihw.png","lines" => "2",],
        "bilbo" => ["name" => "Why Shouldn't I Keep It","blank_url" => "https://api.memegen.link/images/bilbo.png","lines" => "2",],
        "biw" => ["name" => "Baby Insanity Wolf","blank_url" => "https://api.memegen.link/images/biw.png","lines" => "2",],
        "blb" => ["name" => "Bad Luck Brian","blank_url" => "https://api.memegen.link/images/blb.png","lines" => "2",],
        "boat" => ["name" => "I Should Buy a Boat Cat","blank_url" => "https://api.memegen.link/images/boat.png","lines" => "2",],
        "bongo" => ["name" => "Bongo Cat","blank_url" => "https://api.memegen.link/images/bongo.png","lines" => "2",],
        "both" => ["name" => "Why Not Both?","blank_url" => "https://api.memegen.link/images/both.png","lines" => "2",],
        "box" => ["name" => "What's in the box!?","blank_url" => "https://api.memegen.link/images/box.png","lines" => "2",],
        "bs" => ["name" => "This is Bull, Shark","blank_url" => "https://api.memegen.link/images/bs.png","lines" => "2",],
        "buzz" => ["name" => "X, X Everywhere","blank_url" => "https://api.memegen.link/images/buzz.png","lines" => "2",],
        "cake" => ["name" => "Office Space Milton","blank_url" => "https://api.memegen.link/images/cake.png","lines" => "2",],
        "captain" => ["name" => "I am the Captain Now","blank_url" => "https://api.memegen.link/images/captain.png","lines" => "2",],
        "captain-america" => ["name" => "Captain America Elevator Fight Dad Joke","blank_url" => "https://api.memegen.link/images/captain-america.png","lines" => "3",],
        "cb" => ["name" => "Confession Bear","blank_url" => "https://api.memegen.link/images/cb.png","lines" => "2",],
        "cbb" => ["name" => "Communist Bugs Bunny","blank_url" => "https://api.memegen.link/images/cbb.png","lines" => "2",],
        "cbg" => ["name" => "Comic Book Guy","blank_url" => "https://api.memegen.link/images/cbg.png","lines" => "2",],
        "center" => ["name" => "What is this, a Center for Ants?!","blank_url" => "https://api.memegen.link/images/center.png","lines" => "2",],
        "ch" => ["name" => "Captain Hindsight","blank_url" => "https://api.memegen.link/images/ch.png","lines" => "2",],
        "chair" => ["name" => "American Chopper Argument","blank_url" => "https://api.memegen.link/images/chair.png","lines" => "6",],
        "cheems" => ["name" => "Cheems","blank_url" => "https://api.memegen.link/images/cheems.png","lines" => "2",],
        "chosen" => ["name" => "You Were the Chosen One!","blank_url" => "https://api.memegen.link/images/chosen.png","lines" => "2",],
        "cmm" => ["name" => "Change My Mind","blank_url" => "https://api.memegen.link/images/cmm.png","lines" => "1",],
        "country" => ["name" => "What a Country","blank_url" => "https://api.memegen.link/images/country.png","lines" => "2",],
        "crazypills" => ["name" => "I Feel Like I'm Taking Crazy Pills","blank_url" => "https://api.memegen.link/images/crazypills.png","lines" => "2",],
        "crow" => ["name" => "Get Better Material","blank_url" => "https://api.memegen.link/images/crow.png","lines" => "2",],
        "cryingfloor" => ["name" => "Crying on Floor","blank_url" => "https://api.memegen.link/images/cryingfloor.png","lines" => "2",],
        "db" => ["name" => "Distracted Boyfriend","blank_url" => "https://api.memegen.link/images/db.png","lines" => "3",],
        "dbg" => ["name" => "Expectation vs. Reality","blank_url" => "https://api.memegen.link/images/dbg.png","lines" => "2",],
        "dg" => ["name" => "Distracted Girlfriend","blank_url" => "https://api.memegen.link/images/dg.png","lines" => "3",],
        "disastergirl" => ["name" => "Disaster Girl","blank_url" => "https://api.memegen.link/images/disastergirl.png","lines" => "2",],
        "dodgson" => ["name" => "See? Nobody Cares","blank_url" => "https://api.memegen.link/images/dodgson.png","lines" => "2",],
        "doge" => ["name" => "Doge","blank_url" => "https://api.memegen.link/images/doge.png","lines" => "2",],
        "dragon" => ["name" => "What Color Do You Want Your Dragon","blank_url" => "https://api.memegen.link/images/dragon.png","lines" => "1",],
        "drake" => ["name" => "Drakeposting","blank_url" => "https://api.memegen.link/images/drake.png","lines" => "2",],
        "drowning" => ["name" => "Drowning High Five","blank_url" => "https://api.memegen.link/images/drowning.png","lines" => "3",],
        "drunk" => ["name" => "Drunk Baby","blank_url" => "https://api.memegen.link/images/drunk.png","lines" => "2",],
        "ds" => ["name" => "Daily Struggle","blank_url" => "https://api.memegen.link/images/ds.png","lines" => "3",],
        "dsm" => ["name" => "Dating Site Murderer","blank_url" => "https://api.memegen.link/images/dsm.png","lines" => "2",],
        "dwight" => ["name" => "Schrute Facts","blank_url" => "https://api.memegen.link/images/dwight.png","lines" => "2",],
        "elf" => ["name" => "You Sit on a Throne of Lies","blank_url" => "https://api.memegen.link/images/elf.png","lines" => "2",],
        "elmo" => ["name" => "Elmo Choosing Cocaine","blank_url" => "https://api.memegen.link/images/elmo.png","lines" => "5",],
        "ermg" => ["name" => "Ermahgerd","blank_url" => "https://api.memegen.link/images/ermg.png","lines" => "2",],
        "exit" => ["name" => "Left Exit 12 Off Ramp","blank_url" => "https://api.memegen.link/images/exit.png","lines" => "3",],
        "fa" => ["name" => "Forever Alone","blank_url" => "https://api.memegen.link/images/fa.png","lines" => "2",],
        "facepalm" => ["name" => "Facepalm","blank_url" => "https://api.memegen.link/images/facepalm.png","lines" => "2",],
        "fbf" => ["name" => "Foul Bachelor Frog","blank_url" => "https://api.memegen.link/images/fbf.png","lines" => "2",],
        "feelsgood" => ["name" => "Feels Good","blank_url" => "https://api.memegen.link/images/feelsgood.png","lines" => "2",],
        "fetch" => ["name" => "Stop Trying to Make Fetch Happen","blank_url" => "https://api.memegen.link/images/fetch.png","lines" => "2",],
        "fine" => ["name" => "This is Fine","blank_url" => "https://api.memegen.link/images/fine.png","lines" => "2",],
        "firsttry" => ["name" => "First Try!","blank_url" => "https://api.memegen.link/images/firsttry.png","lines" => "2",],
        "fmr" => ["name" => "Fuck Me, Right?","blank_url" => "https://api.memegen.link/images/fmr.png","lines" => "2",],
        "fry" => ["name" => "Futurama Fry","blank_url" => "https://api.memegen.link/images/fry.png","lines" => "2",],
        "fwp" => ["name" => "First World Problems","blank_url" => "https://api.memegen.link/images/fwp.png","lines" => "2",],
        "gandalf" => ["name" => "Confused Gandalf","blank_url" => "https://api.memegen.link/images/gandalf.png","lines" => "2",],
        "gb" => ["name" => "Galaxy Brain","blank_url" => "https://api.memegen.link/images/gb.png","lines" => "4",],
        "gears" => ["name" => "You Know What Really Grinds My Gears?","blank_url" => "https://api.memegen.link/images/gears.png","lines" => "2",],
        "genie" => ["name" => "Genie Lamp","blank_url" => "https://api.memegen.link/images/genie.png","lines" => "2",],
        "ggg" => ["name" => "Good Guy Greg","blank_url" => "https://api.memegen.link/images/ggg.png","lines" => "2",],
        "glasses" => ["name" => "Peter Parker's Glasses","blank_url" => "https://api.memegen.link/images/glasses.png","lines" => "2",],
        "gone" => ["name" => "And It's Gone","blank_url" => "https://api.memegen.link/images/gone.png","lines" => "2",],
        "grave" => ["name" => "Grant Gustin Next To Oliver Queen's Grave","blank_url" => "https://api.memegen.link/images/grave.png","lines" => "3",],
        "gru" => ["name" => "Gru's Plan","blank_url" => "https://api.memegen.link/images/gru.png","lines" => "4",],
        "grumpycat" => ["name" => "Grumpy Cat","blank_url" => "https://api.memegen.link/images/grumpycat.png","lines" => "2",],
        "hagrid" => ["name" => "I Should Not Have Said That","blank_url" => "https://api.memegen.link/images/hagrid.png","lines" => "2",],
        "happening" => ["name" => "It's Happening","blank_url" => "https://api.memegen.link/images/happening.png","lines" => "2",],
        "harold" => ["name" => "Hide the Pain Harold","blank_url" => "https://api.memegen.link/images/harold.png","lines" => "2",],
        "headaches" => ["name" => "Types of Headaches","blank_url" => "https://api.memegen.link/images/headaches.png","lines" => "1",],
        "hipster" => ["name" => "Hipster Barista","blank_url" => "https://api.memegen.link/images/hipster.png","lines" => "2",],
        "home" => ["name" => "We Have Food at Home","blank_url" => "https://api.memegen.link/images/home.png","lines" => "3",],
        "icanhas" => ["name" => "I Can Has Cheezburger?","blank_url" => "https://api.memegen.link/images/icanhas.png","lines" => "2",],
        "imsorry" => ["name" => "Oh, I'm Sorry, I Thought This Was America","blank_url" => "https://api.memegen.link/images/imsorry.png","lines" => "2",],
        "inigo" => ["name" => "Inigo Montoya","blank_url" => "https://api.memegen.link/images/inigo.png","lines" => "2",],
        "interesting" => ["name" => "The Most Interesting Man in the World","blank_url" => "https://api.memegen.link/images/interesting.png","lines" => "2",],
        "ive" => ["name" => "Jony Ive Redesigns Things","blank_url" => "https://api.memegen.link/images/ive.png","lines" => "2",],
        "iw" => ["name" => "Insanity Wolf","blank_url" => "https://api.memegen.link/images/iw.png","lines" => "2",],
        "jd" => ["name" => "Joseph Ducreux","blank_url" => "https://api.memegen.link/images/jd.png","lines" => "2",],
        "jetpack" => ["name" => "Nothing To Do Here","blank_url" => "https://api.memegen.link/images/jetpack.png","lines" => "2",],
        "jim" => ["name" => "Jim Halpert Pointing to Whiteboard","blank_url" => "https://api.memegen.link/images/jim.png","lines" => "2",],
        "joker" => ["name" => "It's Simple, Kill the Batman","blank_url" => "https://api.memegen.link/images/joker.png","lines" => "2",],
        "jw" => ["name" => "Probably Not a Good Idea","blank_url" => "https://api.memegen.link/images/jw.png","lines" => "2",],
        "keanu" => ["name" => "Conspiracy Keanu","blank_url" => "https://api.memegen.link/images/keanu.png","lines" => "2",],
        "kermit" => ["name" => "But That's None of My Business","blank_url" => "https://api.memegen.link/images/kermit.png","lines" => "2",],
        "khaby-lame" => ["name" => "Khaby Lame Shrug","blank_url" => "https://api.memegen.link/images/khaby-lame.png","lines" => "2",],
        "kk" => ["name" => "Karate Kyle","blank_url" => "https://api.memegen.link/images/kk.png","lines" => "2",],
        "kombucha" => ["name" => "Kombucha Girl","blank_url" => "https://api.memegen.link/images/kombucha.png","lines" => "2",],
        "kramer" => ["name" => "Kramer, What's Going On In There?","blank_url" => "https://api.memegen.link/images/kramer.png","lines" => "2",],
        "leo" => ["name" => "Leo Strutting","blank_url" => "https://api.memegen.link/images/leo.png","lines" => "2",],
        "light" => ["name" => "Everything the Light Touches is Our Kingdom","blank_url" => "https://api.memegen.link/images/light.png","lines" => "2",],
        "live" => ["name" => "Do It Live!","blank_url" => "https://api.memegen.link/images/live.png","lines" => "2",],
        "ll" => ["name" => "Laughing Lizard","blank_url" => "https://api.memegen.link/images/ll.png","lines" => "2",],
        "lrv" => ["name" => "Laundry Room Viking","blank_url" => "https://api.memegen.link/images/lrv.png","lines" => "2",],
        "made" => ["name" => "I Made This","blank_url" => "https://api.memegen.link/images/made.png","lines" => "5",],
        "mb" => ["name" => "Member Berries","blank_url" => "https://api.memegen.link/images/mb.png","lines" => "2",],
        "michael-scott" => ["name" => "Michael Scott No God No","blank_url" => "https://api.memegen.link/images/michael-scott.png","lines" => "2",],
        "millers" => ["name" => "You Guys Are Getting Paid?","blank_url" => "https://api.memegen.link/images/millers.png","lines" => "4",],
        "mini-keanu" => ["name" => "Mini Keanu Reeves","blank_url" => "https://api.memegen.link/images/mini-keanu.png","lines" => "2",],
        "mmm" => ["name" => "Minor Mistake Marvin","blank_url" => "https://api.memegen.link/images/mmm.png","lines" => "2",],
        "money" => ["name" => "Shut Up and Take My Money!","blank_url" => "https://api.memegen.link/images/money.png","lines" => "2",],
        "mordor" => ["name" => "One Does Not Simply Walk into Mordor","blank_url" => "https://api.memegen.link/images/mordor.png","lines" => "2",],
        "morpheus" => ["name" => "Matrix Morpheus","blank_url" => "https://api.memegen.link/images/morpheus.png","lines" => "2",],
        "mouth" => ["name" => "Woman Holding Dog's Mouth","blank_url" => "https://api.memegen.link/images/mouth.png","lines" => "3",],
        "mw" => ["name" => "I Guarantee It","blank_url" => "https://api.memegen.link/images/mw.png","lines" => "2",],
        "nails" => ["name" => "Guy Hammering Nails Into Sand","blank_url" => "https://api.memegen.link/images/nails.png","lines" => "3",],
        "nice" => ["name" => "So I Got That Goin' For Me, Which is Nice","blank_url" => "https://api.memegen.link/images/nice.png","lines" => "2",],
        "noah" => ["name" => "What the Hell is This?","blank_url" => "https://api.memegen.link/images/noah.png","lines" => "4",],
        "noidea" => ["name" => "I Have No Idea What I'm Doing","blank_url" => "https://api.memegen.link/images/noidea.png","lines" => "2",],
        "ntot" => ["name" => "No Take, Only Throw","blank_url" => "https://api.memegen.link/images/ntot.png","lines" => "3",],
        "oag" => ["name" => "Overly Attached Girlfriend","blank_url" => "https://api.memegen.link/images/oag.png","lines" => "2",],
        "officespace" => ["name" => "That Would Be Great","blank_url" => "https://api.memegen.link/images/officespace.png","lines" => "2",],
        "older" => ["name" => "An Older Code Sir, But It Checks Out","blank_url" => "https://api.memegen.link/images/older.png","lines" => "2",],
        "oprah" => ["name" => "","blank_url" => "https://api.memegen.link/images/oprah.png","lines" => "2",],
        "panik-kalm-panik" => ["name" => "Panik Kalm Panik","blank_url" => "https://api.memegen.link/images/panik-kalm-panik.png","lines" => "3",],
        "patrick" => ["name" => "Push it somewhere else Patrick","blank_url" => "https://api.memegen.link/images/patrick.png","lines" => "2",],
        "perfection" => ["name" => "Perfection","blank_url" => "https://api.memegen.link/images/perfection.png","lines" => "6",],
        "persian" => ["name" => "Persian Cat Room Guardian","blank_url" => "https://api.memegen.link/images/persian.png","lines" => "2",],
        "philosoraptor" => ["name" => "Philosoraptor","blank_url" => "https://api.memegen.link/images/philosoraptor.png","lines" => "2",],
        "pigeon" => ["name" => "Is This a Pigeon?","blank_url" => "https://api.memegen.link/images/pigeon.png","lines" => "3",],
        "pooh" => ["name" => "Tuxedo Winnie the Pooh","blank_url" => "https://api.memegen.link/images/pooh.png","lines" => "2",],
        "ptj" => ["name" => "Phoebe Teaching Joey","blank_url" => "https://api.memegen.link/images/ptj.png","lines" => "8",],
        "puffin" => ["name" => "Unpopular opinion puffin","blank_url" => "https://api.memegen.link/images/puffin.png","lines" => "2",],
        "red" => ["name" => "Oh, Is That What We're Going to Do Today?","blank_url" => "https://api.memegen.link/images/red.png","lines" => "2",],
        "regret" => ["name" => "I Immediately Regret This Decision!","blank_url" => "https://api.memegen.link/images/regret.png","lines" => "2",],
        "remembers" => ["name" => "Pepperidge Farm Remembers","blank_url" => "https://api.memegen.link/images/remembers.png","lines" => "2",],
        "reveal" => ["name" => "Scooby Doo Reveal","blank_url" => "https://api.memegen.link/images/reveal.png","lines" => "4",],
        "right" => ["name" => "Anakin and Padme Change the World For the Better","blank_url" => "https://api.memegen.link/images/right.png","lines" => "5",],
        "rollsafe" => ["name" => "Roll Safe","blank_url" => "https://api.memegen.link/images/rollsafe.png","lines" => "2",],
        "sad-biden" => ["name" => "Sad Joe Biden","blank_url" => "https://api.memegen.link/images/sad-biden.png","lines" => "2",],
        "sad-boehner" => ["name" => "Sad John Boehner","blank_url" => "https://api.memegen.link/images/sad-boehner.png","lines" => "2",],
        "sad-bush" => ["name" => "Sad George Bush","blank_url" => "https://api.memegen.link/images/sad-bush.png","lines" => "2",],
        "sad-clinton" => ["name" => "Sad Bill Clinton","blank_url" => "https://api.memegen.link/images/sad-clinton.png","lines" => "2",],
        "sad-obama" => ["name" => "Sad Barack Obama","blank_url" => "https://api.memegen.link/images/sad-obama.png","lines" => "2",],
        "sadfrog" => ["name" => "Feels Bad Man","blank_url" => "https://api.memegen.link/images/sadfrog.png","lines" => "2",],
        "saltbae" => ["name" => "Salt Bae","blank_url" => "https://api.memegen.link/images/saltbae.png","lines" => "2",],
        "same" => ["name" => "They're The Same Picture","blank_url" => "https://api.memegen.link/images/same.png","lines" => "3",],
        "sarcasticbear" => ["name" => "Sarcastic Bear","blank_url" => "https://api.memegen.link/images/sarcasticbear.png","lines" => "2",],
        "say" => ["name" => "Say the Line, Bart!","blank_url" => "https://api.memegen.link/images/say.png","lines" => "2",],
        "sb" => ["name" => "Scumbag Brain","blank_url" => "https://api.memegen.link/images/sb.png","lines" => "2",],
        "scc" => ["name" => "Sudden Clarity Clarence","blank_url" => "https://api.memegen.link/images/scc.png","lines" => "2",],
        "seagull" => ["name" => "Inhaling Seagull","blank_url" => "https://api.memegen.link/images/seagull.png","lines" => "2",],
        "sf" => ["name" => "Sealed Fate","blank_url" => "https://api.memegen.link/images/sf.png","lines" => "2",],
        "sk" => ["name" => "Skeptical Third World Kid","blank_url" => "https://api.memegen.link/images/sk.png","lines" => "2",],
        "ski" => ["name" => "Super Cool Ski Instructor","blank_url" => "https://api.memegen.link/images/ski.png","lines" => "2",],
        "slap" => ["name" => "Will Smith Slapping Chris Rock","blank_url" => "https://api.memegen.link/images/slap.png","lines" => "2",],
        "snek" => ["name" => "Skeptical Snake","blank_url" => "https://api.memegen.link/images/snek.png","lines" => "2",],
        "soa" => ["name" => "Seal of Approval","blank_url" => "https://api.memegen.link/images/soa.png","lines" => "2",],
        "sohappy" => ["name" => "I Would Be So Happy","blank_url" => "https://api.memegen.link/images/sohappy.png","lines" => "2",],
        "sohot" => ["name" => "So Hot Right Now","blank_url" => "https://api.memegen.link/images/sohot.png","lines" => "2",],
        "soup-nazi" => ["name" => "No Soup for You","blank_url" => "https://api.memegen.link/images/soup-nazi.png","lines" => "2",],
        "sparta" => ["name" => "This is Sparta!","blank_url" => "https://api.memegen.link/images/sparta.png","lines" => "2",],
        "spiderman" => ["name" => "Spider-Man Pointing at Spider-Man","blank_url" => "https://api.memegen.link/images/spiderman.png","lines" => "2",],
        "spongebob" => ["name" => "Mocking Spongebob","blank_url" => "https://api.memegen.link/images/spongebob.png","lines" => "2",],
        "ss" => ["name" => "Scumbag Steve","blank_url" => "https://api.memegen.link/images/ss.png","lines" => "2",],
        "stew" => ["name" => "Baby, You've Got a Stew Going","blank_url" => "https://api.memegen.link/images/stew.png","lines" => "2",],
        "stonks" => ["name" => "Stonks","blank_url" => "https://api.memegen.link/images/stonks.png","lines" => "2",],
        "stop" => ["name" => "Stop It Patrick You're Scaring Him","blank_url" => "https://api.memegen.link/images/stop.png","lines" => "6",],
        "stop-it" => ["name" => "Stop It, Get Some Help","blank_url" => "https://api.memegen.link/images/stop-it.png","lines" => "2",],
        "success" => ["name" => "Success Kid","blank_url" => "https://api.memegen.link/images/success.png","lines" => "2",],
        "tenguy" => ["name" => "10 Guy","blank_url" => "https://api.memegen.link/images/tenguy.png","lines" => "2",],
        "toohigh" => ["name" => "The Rent Is Too Damn High","blank_url" => "https://api.memegen.link/images/toohigh.png","lines" => "2",],
        "touch" => ["name" => "Principal Skinner","blank_url" => "https://api.memegen.link/images/touch.png","lines" => "2",],
        "tried" => ["name" => "At Least You Tried","blank_url" => "https://api.memegen.link/images/tried.png","lines" => "2",],
        "trump" => ["name" => "Donald Trump","blank_url" => "https://api.memegen.link/images/trump.png","lines" => "2",],
        "ugandanknuck" => ["name" => "Ugandan Knuckles","blank_url" => "https://api.memegen.link/images/ugandanknuck.png","lines" => "2",],
        "vince" => ["name" => "Vince McMahon Reaction","blank_url" => "https://api.memegen.link/images/vince.png","lines" => "3",],
        "wallet" => ["name" => "Patrick Star's Wallet","blank_url" => "https://api.memegen.link/images/wallet.png","lines" => "8",],
        "waygd" => ["name" => "What Are Ya Gonna Do?","blank_url" => "https://api.memegen.link/images/waygd.png","lines" => "2",],
        "wddth" => ["name" => "We Don't Do That Here","blank_url" => "https://api.memegen.link/images/wddth.png","lines" => "2",],
        "whatyear" => ["name" => "What Year Is It?","blank_url" => "https://api.memegen.link/images/whatyear.png","lines" => "2",],
        "winter" => ["name" => "Winter is coming","blank_url" => "https://api.memegen.link/images/winter.png","lines" => "2",],
        "wkh" => ["name" => "Who Killed Hannibal?","blank_url" => "https://api.memegen.link/images/wkh.png","lines" => "3",],
        "woman-cat" => ["name" => "Woman Yelling at a Cat","blank_url" => "https://api.memegen.link/images/woman-cat.png","lines" => "2",],
        "wonka" => ["name" => "Condescending Wonka","blank_url" => "https://api.memegen.link/images/wonka.png","lines" => "2",],
        "worst" => ["name" => "The Worst Day Of Your Life So Far","blank_url" => "https://api.memegen.link/images/worst.png","lines" => "2",],
        "xy" => ["name" => "X all the Y","blank_url" => "https://api.memegen.link/images/xy.png","lines" => "2",],
        "yallgot" => ["name" => "Y'all Got Any More of Them","blank_url" => "https://api.memegen.link/images/yallgot.png","lines" => "2",],
        "yodawg" => ["name" => "Xzibit Yo Dawg","blank_url" => "https://api.memegen.link/images/yodawg.png","lines" => "2",],
        "yuno" => ["name" => "Y U NO Guy","blank_url" => "https://api.memegen.link/images/yuno.png","lines" => "2",],
        "zero-wing" => ["name" => "All Your Base Are Belong to Us","blank_url" => "https://api.memegen.link/images/zero-wing.png","lines" => "1",],
        ];
	}

	/**
	 * @param string $query What to search for
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchMemes(string $query, int $offset = 0, int $limit = 5): array {

        #TODO: refresh template list online every 24h?
        #TODO: optimize search? 
        # For now just brute force through a hardcoded list:
        $distanceArr = [];
        foreach($this->memeTemplates as $memeShortName => $memeInfo) {
            
            $distanceArr[$memeShortName] = levenshtein(strtoupper($query),strtoupper($memeInfo['name']),0,1,1);
            

        }

        asort($distanceArr);

        $distanceArr = array_slice($distanceArr,$offset,$limit, True);

		$result = [];
        foreach (array_keys($distanceArr) as $key) {
            $result[] = ["memeId" => $key, "alt" => $this->memeTemplates[$key]["name"], "blank_url" =>$this->memeTemplates[$key]["blank_url"], 'lines' =>$this->memeTemplates[$key]['lines']];
        }

        return $result;

	}

	

	public function getMemeInfo(string $memeName): ?array {
		#return $this->request('v1/photos/' . $photoId);

        if (!isset($this->memeTemplates[$memeName])) {
            return null;
        }

        return $this->memeTemplates[$memeName];
	}

	public function getMemeContent(string $memeId, ?array $captions): ?array {
        #TODO: Resize images as necessary
		#$photoInfo = $this->getPhotoInfo($photoId);
		if (isset($this->memeTemplates[$memeId])) {
			try {
                
                if($captions === null){
                    $fullUrl = Application::MEME_SERVICE_URL . '/images/' . $memeId . ".jpg";
                }
                else {
                    $fullUrl = Application::MEME_SERVICE_URL . '/images/' . $memeId ;
                    foreach($captions as $caption) {
                        if($caption === '') {
                            $fullUrl .= '/_';
                        } else {
                            $fullUrl .= '/' . $caption;
                        }
                        
                    }
                    $fullUrl .= ".jpg";
                }
            
                           
				$memeResponse = $this->client->get($fullUrl);
				return [
					'body' => $memeResponse->getBody(),
					'headers' => $memeResponse->getHeaders(),
				];
			} catch (Exception|Throwable $e) {
				$this->logger->warning('Memegen meme content request error: ' . $e->getMessage(), ['app' => Application::APP_ID]);
				return null;
			}
		}
		return null;
	}
}
