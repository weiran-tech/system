@extends('py-system::tpl.mail')
@section('mail-main')
	<h3>{!! $title ?? '' !!}</h3>
	@include('py-system::tpl.mail.article_start')
	{!! $content ?? '' !!}
	@include('py-system::tpl.mail.article_end')
@endsection