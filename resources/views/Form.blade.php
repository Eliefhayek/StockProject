<!-- resources/views/submit-form.blade.php -->
@csrf
<form method="GET" action="{{ url('api/stock') }}">
    <input type="text" name="company_name" placeholder="Enter the company name">
    <button type="submit">Submit</button>
</form>
