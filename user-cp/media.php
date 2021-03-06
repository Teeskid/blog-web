<?php
/**
 * Project: Blog Management System With Sevida-Like UI
 * Developed By: Ahmad Tukur Jikamshi
 *
 * @facebook: amaedyteeskid
 * @twitter: amaedyteeskid
 * @instagram: amaedyteeskid
 * @whatsapp: +2348145737179
 */
require( __DIR__ . '/Load.php' );
require( ABSPATH . BASE_UTIL . '/HtmlUtil.php' );

$option = request( 'tab', 'rowType', 'sort' );

$where = [ 'Post.rowType=?' ];
switch( $option->tab ){
	case 'self':
		$where[] = 'Post.author=' . $_db->quote($_usr->id);
		break;
	default:
		$option->tab = 'all';
}
switch( $option->rowType ){
	case 'image':
		$where[] = sprintf( 'Post.mimeType IN (%s)', $_db->quoteList(FORMAT_IMAGE) );
		break;
	case 'audio':
		$where[] = sprintf( 'Post.mimeType IN (%s)', $_db->quoteList(FORMAT_AUDIO) );
		break;
	case 'video':
		$where[] = sprintf( 'Post.mimeType IN (%s)', $_db->quoteList(FORMAT_VIDEO) );
		break;
	default:
		$option->rowType = 'all';
		// $where[] = 'Post.mimeType != NULL';
}
switch( $option->sort ){
	case 'nameAsc':
		$sort = 'Post.title ASC';
		break;
	case 'nameDesc':
		$sort = 'Post.title DESC';
		break;
	case 'dateAsc':
		$sort = 'Post.datePosted ASC';
		break;
	default:
		$option->sort = 'dateDesc';
		$sort = 'Post.datePosted DESC';
}
$where = implode( ' AND ', $where );

$paging = $_db->prepare( 'SELECT COUNT(*) FROM Post WHERE ' . $where );
$paging->execute( [ 'media' ] );
$paging = parseInt( $paging->fetchColumn() );
$paging = new Paging( 20, $paging );

$mediaList = $_db->prepare(
	'SELECT Post.id, Post.title, Post.datePosted AS uploaded, PostMeta.metaValue AS metaValue, Uzer.userName AS uploader FROM Post LEFT JOIN PostMeta ON PostMeta.postId=Post.id AND PostMeta.metaKey=? ' .
	'LEFT JOIN Uzer ON Uzer.id=Post.author WHERE ' . $where . ' ORDER BY ' . $sort . ' LIMIT ' . $paging->getLimit()
);
$mediaList->execute( [ 'media_metadata', 'media' ] );
$mediaList = $mediaList->fetchAll( PDO::FETCH_CLASS, 'Media' );

$HTML = sprintf( '/user-cp/media.php?tab=%s&type=%s&sort=%s', $option->tab, $option->rowType, $option->sort );
initHtmlPage( 'Media Library', $HTML );
$HTML->addPageMeta( Page::META_CSS_CODE, <<<'EOS'
@media (max-width: 767px) {
	td h4 {
		font-weight:100;
		font-style:normal;
	}
	th:nth-child(4), td:nth-child(4), th:nth-child(5), td:nth-child(5) {
		display:none;
	}
}
@media (max-width: 991px) {
	th:nth-child(5), td:nth-child(5) {
		display:none;
	}
}
EOS
);
include_once( __DIR__ . '/header.php' );
?>
<nav aria-role="breadcrumb">
	<ol class="breadcrumb my-3">
		<li class="breadcrumb-item"><a href="index.php">Home</a></li>
		<li class="breadcrumb-item active" aria-current="page">Media</li>
	</ol>
</nav>
<h2>Media Library <a href="media-new.php" role="button" class="badge">Upload</a></h2>
<div class="card bg-light text-dark">
	<div class="card-header">Screen Option</div>
	<div class="card-body">
		<form class="form-inline" action="<?=$_SERVER['REQUEST_URI']?>" method="get">
			<input type="hidden" name="tab" value="<?=$option->tab?>" />
			<div class="mb-3">
				<label for="select-format" class="form-label">Select Format</label>
				<select id="select-format" name="type" class="form-control">
<?php
foreach( [ 'nameAsc' => 'Name Ascending', 'nameDesc' => 'Name Descending', 'dateAsc' => 'Date Ascending', 'dateDesc' => 'Date Descending' ] as $index => $entry ) {
?>
					<option value="<?=$index?>"<?=($index==$option->rowType?' selected':'')?>><?=$entry?></option>
<?php
}
?>
				</select>
			</div>
			<div class="mb-3">
				<label for="select-sort" class="form-label">Sort By</label>
				<select id="select-sort" name="sort" class="form-control">
<?php
foreach( [ 'nameAsc' => 'Name Ascending', 'nameDesc' => 'Name Descending', 'dateAsc' => 'Date Ascending', 'dateDesc' => 'Date Descending' ] as $index => $entry ) {
?>
					<option value="<?=$index?>"<?=($index==$option->sort?' selected':'')?>><?=$entry?></option>
<?php
}
?>
				</select>
			</div>
			<div class="mb-3">
				<button type="submit" class="btn btn-primary">Apply Filter</button>
			</div>
		</form>
	</div>
</div>
<div class="card panel-info">
	<div class="card-header">Uploaded Files</div>
	<ul class="nav nav-tabs">
<?php
foreach( [ 'all' => 'All', 'self' => 'By You' ] as $index => $entry ) {
?>
		<li role="presentation"<?=($option->tab==$index?' class="active"':'')?>><a role="tab" target="_self" href="?tab=<?=$index?>"><?=$entry?></a></li>
<?php
}
?>
	</ul>
	<table id="medialist" class="table table-striped table-hover">
		<tr>
			<th class="text-center" width="20">#</th>
			<th>File Name</th>
			<th width="50"></th>
			<th>Uploaded By</th>
			<th>Date</th>
		</tr>
<?php
if( ! isset($mediaList[0]) ) {
?>
	<tr class="text-center info"><td colspan="5">No data available</td></tr>
<?php
}
foreach( $mediaList as $index => &$entry ) {
	$metaValue = json_decode( $entry->metaValue );
	$metaValue->fileSize = escHtml( Media::formatSize( $metaValue->fileSize ?? 0 ) );
	$entry->uploaded = escHtml($entry->uploaded);
	$entry->uploader = escHtml($entry->uploader);
	$entry->title = escHtml($entry->title);
	$entry->domId = 'Bt_' . $entry->id;
	$entry->id = escHtml($entry->id);
?>
		<tr data-id="<?=$entry->id?>">
			<td class="text-center"><?=++$index?></td>
			<td><?=$entry->title?></td>
			<td class="text-center">
				<div class="dropdown">
					<button id="<?=$entry->domId?>" type="button" aria-haspopup="true" aria-expanded="false" data-bs-toggle="dropdown" class="btn btn-primary btn-xs">ACTION <span class="caret"></span></button>
					<ul class="dropdown-menu" aria-labelledby="<?=$entry->domId?>">
						<li><a href="#" data-action="modify">Edit</a></li>
						<li><a href="#" data-action="unlink" class="text-danger">Delete</a></li>
					</ul>
				</div>
			</td>
			<td><?=$entry->uploader?></td>
			<td><?=$entry->uploaded?></td>
		</tr>
<?php
	$index = $entry = null;
}
?>
	</table>
	<div class="card-footer">
		<a href="#" id="select-all">Select All - </a>
		<span> With Selected: </span>
		<button type="submit" name="action" value="unlink" class="btn-small">Delete</button>
<?php
doHtmlPaging( $paging, $HTML->path )
?>
	</div>
</div>
<?php
addPageJsFile( 'js/jquery.action-button.js' );
function onPageJsCode() {
	$(document).ready(function(){
		$("table#medialist a[data-action]").actionBtn({
			unlink: "../api/media-edit.php",
			modify: function(id) {
				window.location = 'media-edit.php?action=modify&id=' + id;
			}
		});
	});
EOS
);
include_once( __DIR__ . '/footer.php' );
