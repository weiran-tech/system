@extends('weiran-system::tpl.mail')
@section('mail-main')
	<h3>{!! $title ?? '' !!}</h3>
	@include('weiran-system::tpl.mail.article_start')
	{!! $content ?? '' !!}
	@include('weiran-system::tpl.mail.article_end')
@endsection