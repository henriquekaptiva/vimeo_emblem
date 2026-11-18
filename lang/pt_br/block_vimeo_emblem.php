<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Portuguese (Brazil) language strings for the Vimeo emblem block.
 *
 * @package   block_vimeo_emblem
 * @copyright 2026 Henrique
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Emblema por vídeo Vimeo';
$string['vimeo_emblem'] = 'Emblema por vídeo Vimeo';
$string['vimeo_emblem:addinstance'] = 'Adicionar um novo bloco Emblema por vídeo Vimeo';
$string['vimeo_emblem:myaddinstance'] = 'Adicionar um novo bloco Emblema por vídeo Vimeo ao Painel';

$string['show_as_card'] = 'Exibir bloco como card';
$string['show_header'] = 'Exibir cabeçalho do bloco';
$string['custom_title'] = 'Título personalizado do bloco';
$string['custom_subtitle'] = 'Subtítulo personalizado do bloco';
$string['content'] = 'Conteúdo (embed do Vimeo)';
$string['content_help'] = 'Cole o HTML de embed do Vimeo (iframe). O bloco rastreia os segundos únicos assistidos e emite o emblema selecionado ao atingir o percentual configurado.';

$string['badgesettings'] = 'Configurações do emblema';
$string['badgeid'] = 'Emblema a emitir';
$string['badgeid_help'] = 'Escolha um emblema de curso ativo. Ele é emitido automaticamente quando o usuário assiste pelo menos o percentual configurado de tempo único do vídeo. Crie e ative o emblema no curso antes.';
$string['nobadge'] = 'Não emitir emblema';
$string['nobadgesavailable'] = 'Nenhum emblema de curso encontrado. Crie um emblema no curso antes de selecioná-lo aqui.';
$string['threshold'] = 'Percentual mínimo assistido';
$string['threshold_help'] = 'Percentual de segundos únicos que o usuário precisa assistir para receber o emblema (por exemplo 95). Pular até o final sem assistir não conta.';
$string['prevent_seek'] = 'Impedir avançar o vídeo';
$string['prevent_seek_help'] = 'Se ativado, o espectador não consegue pular além do último segundo já assistido.';
$string['show_progress'] = 'Exibir progresso da visualização';
$string['show_progress_help'] = 'Mostra uma barra de progresso abaixo do vídeo para o usuário autenticado.';

$string['progresslabel'] = 'Assistido: {$a->percent}% (emblema em {$a->threshold}%)';
$string['badgeissued'] = 'Emblema emitido. Você assistiu a parte necessária do vídeo.';
$string['alreadycompleted'] = 'Você já concluiu este vídeo.';

$string['privacy:metadata'] = 'O bloco Emblema por vídeo Vimeo armazena o progresso de visualização de cada usuário para emitir o emblema.';
$string['privacy:metadata:block_vimeo_emblem'] = 'Progresso de visualização vinculado a uma instância do bloco.';
$string['privacy:metadata:instanceid'] = 'O ID da instância do bloco.';
$string['privacy:metadata:userid'] = 'O ID do usuário que assistiu ao vídeo.';
$string['privacy:metadata:videoid'] = 'O ID do vídeo no Vimeo.';
$string['privacy:metadata:watched'] = 'Os segundos únicos assistidos.';
$string['privacy:metadata:duration'] = 'A duração do vídeo em segundos.';
$string['privacy:metadata:percent'] = 'O percentual único assistido.';
$string['privacy:metadata:completed'] = 'Se o percentual mínimo foi atingido.';
$string['privacy:metadata:badgeissued'] = 'Se o emblema foi emitido.';
$string['privacy:metadata:timecreated'] = 'Quando o rastreamento começou.';
$string['privacy:metadata:timemodified'] = 'Quando o progresso foi atualizado pela última vez.';
$string['privacy:metadata:vimeo'] = 'O bloco carrega o player da Vimeo no navegador. A Vimeo recebe a requisição feita para reproduzir o vídeo.';
$string['privacy:metadata:vimeo:ipaddress'] = 'O endereço IP do usuário é enviado à Vimeo pelo navegador quando o player é carregado.';
$string['privacy:metadata:vimeo:videoid'] = 'O ID do vídeo na Vimeo é solicitado à Vimeo para que o player seja exibido.';
