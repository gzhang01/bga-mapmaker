
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- mapmaker implementation : © <George Zhang> <gkzhang01@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `counties` (
    `coord_x` smallint(5) NOT NULL,
    `coord_y` smallint(5) NOT NULL,
    `county_player` varchar(6) NOT NULL,
    `county_lean` smallint(5) unsigned NOT NULL,
    `district` INT DEFAULT NULL,
    `district_placement` boolean NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`coord_x`, `coord_y`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `edges` (
    `county_1_x` smallint(5) NOT NULL,
    `county_1_y` smallint(5) NOT NULL,
    `county_2_x` smallint(5) NOT NULL,
    `county_2_y` smallint(5) NOT NULL,
    `is_placed` boolean NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`county_1_x`, `county_1_y`, `county_2_x`, `county_2_y`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `districts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `player_color` VARCHAR(6) DEFAULT NULL,
    `possible_winners` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;