<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class CSGalleryLang
{
  public $prev = "Prev";
  public $next = "Next";
  public $search = "Search";
  public $textSearch = "Textsearch:";
  public $total = "Total:";
  public $showing = "Showing ";
  public $to = " to ";
  public $ofTotal = " of total ";
  public $allTypes = "All types";
  public $images = "Images";
  public $documents = "Documents";
  public $videos = "Videos";
  public $sounds = "Sounds";
  public $allCategories = "All categories";
  public $allPlaces = "All places";
  public $close = "Close";
	public $backToArchive = "back to archive";

  public function __construct($lang){
    $this->initLanguage($lang);
  }
  private function initLanguage($lang){
    if($lang == "se")
    {
      $this->prev = "Föreg";
      $this->next = "Nästa";
      $this->search = "Sök";
      $this->textSearch = "Textsök:";
      $this->total = "Totalt:";
      $this->showing = "Visar ";
      $this->to = " till ";
      $this->ofTotal = " av totalt ";
      $this->allTypes = "Alla typer";
      $this->images = "Bilder";
      $this->documents = "Dokuments";
      $this->videos = "Videos";
      $this->sounds = "Ljud";
      $this->allCategories = "Alla kategorier ";
      $this->allPlaces = "Alla platser ";
      $this->close = "Stäng";
			$this->backToArchive = "tillbaka till arkivet";
    }
    else //en
    {
      $this->prev = "Prev";
      $this->next = "Next";
      $this->search = "Search";
      $this->textSearch = "Textsearch:";
      $this->total = "Total:";
      $this->showing = "Showing ";
      $this->to = " to ";
      $this->ofTotal = " of total ";
      $this->allTypes = "All types";
      $this->images = "Images";
      $this->documents = "Documents";
      $this->videos = "Videos";
      $this->sounds = "Sounds";
      $this->allCategories = "All categories ";
      $this->allPlaces = "All places ";
      $this->close = "Close";
			$this->backToArchive = "back to archive";
    }
  }
}
?>
