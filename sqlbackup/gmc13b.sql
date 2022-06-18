-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 18/03/2022 às 01:18
-- Versão do servidor: 5.7.34-log
-- Versão do PHP: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gmc13b`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `competing`
--

CREATE TABLE `competing` (
  `idx` int(11) NOT NULL,
  `ipx` int(11) NOT NULL,
  `map` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `player_num` int(7) NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config`
--

CREATE TABLE `config` (
  `idx` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `force_disconnect` tinyint(1) NOT NULL DEFAULT '0',
  `is_big_lobby` tinyint(1) NOT NULL DEFAULT '0',
  `dev_ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `next_lobby_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cooldown_s` int(11) NOT NULL DEFAULT '60',
  `playing_time_s` int(11) NOT NULL DEFAULT '230',
  `max_afk_s` int(11) NOT NULL DEFAULT '10',
  `tick_s` float NOT NULL DEFAULT '0.18',
  `extra_sync_s` int(11) NOT NULL DEFAULT '10',
  `accept_info_s` int(11) NOT NULL DEFAULT '10',
  `start_checks_s` int(11) NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `config`
--

INSERT INTO `config` (`idx`, `version`, `force_disconnect`, `is_big_lobby`, `dev_ip`, `next_lobby_dt`, `cooldown_s`, `playing_time_s`, `max_afk_s`, `tick_s`, `extra_sync_s`, `accept_info_s`, `start_checks_s`) VALUES
(1, 3, 0, 0, 0, '2022-03-14 05:01:06', 20, 20, 20, 0.18, 0, 5, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lobby`
--

CREATE TABLE `lobby` (
  `idx` int(11) NOT NULL,
  `ipx` int(11) NOT NULL,
  `ent_index` int(11) DEFAULT NULL,
  `map` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pos` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ang` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `used_chat` tinyint(1) NOT NULL DEFAULT '0',
  `is_firing` int(2) DEFAULT '0',
  `last_refresh_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `start_dt` datetime DEFAULT NULL,
  `end_dt` datetime DEFAULT NULL,
  `status` int(3) NOT NULL DEFAULT '0'
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `statistics`
--

CREATE TABLE `statistics` (
  `idx` int(11) NOT NULL,
  `lobby_dt` datetime NOT NULL,
  `map` varchar(100) DEFAULT NULL,
  `ipx_num` int(11) DEFAULT NULL,
  `player_num` int(7) DEFAULT NULL,
  `killed_player_num` int(7) DEFAULT NULL,
  `playing_time_s` int(11) DEFAULT NULL,
  `result` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `waiting`
--

CREATE TABLE `waiting` (
  `idx` int(11) NOT NULL,
  `ipx` int(11) NOT NULL,
  `map` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `player_num` int(7) NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `competing`
--
ALTER TABLE `competing`
  ADD PRIMARY KEY (`idx`) USING BTREE;

--
-- Índices de tabela `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`idx`);

--
-- Índices de tabela `lobby`
--
ALTER TABLE `lobby`
  ADD PRIMARY KEY (`idx`) USING BTREE;

--
-- Índices de tabela `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`idx`);

--
-- Índices de tabela `waiting`
--
ALTER TABLE `waiting`
  ADD PRIMARY KEY (`idx`) USING BTREE;

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `competing`
--
ALTER TABLE `competing`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `config`
--
ALTER TABLE `config`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `lobby`
--
ALTER TABLE `lobby`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `statistics`
--
ALTER TABLE `statistics`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `waiting`
--
ALTER TABLE `waiting`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
