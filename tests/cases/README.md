# The Lax test suite

## Feed parsing tests

The tests under the `JSON`, `XML`, and `HTTP` directories are parser tests
with a shared format and test harness. The test harness is largely contained
in the `AbstractParserTestCase.php` file, which contains the logic to
transform the tests themselves into native PHP input and output.

Tests are series of YAML files each containing a map (an object), each entry
an individual test which is itself a map. Tests have the following keys:

- `input`: The test input as a string, or a map (see below)
- `output`: The result of the parsing upon success; described 
    in more detail below
- `exception`: The exception ID thrown upon failure
- `type`: An HTTP Content-Type (with or without parameters) for 
    the document
- `doc_url`: A fictitious URL where a newsfeed might be located,
    used for relative URL resolution

The 'input' key along with either 'output' or 'exception' are required for
all tests. All other keys may or may not be omitted in a test.

The test output is necessarily mangled due to the limits of YAML:

- Any field which should be an absolute URL is written as a string; relative
    URLs are represented as a sequence with the relative part first, followed
    by the base that is be applied to it
- Any collections are represented as sequences of maps
- Rich text can either be supplied as a string (which represents a Text object
    with plain-text content) or as a map with any of the properties of the
    Text class listed

If the test input is a map, it represents a response from an HTTP server,
with the following keys:

- `status`: The response status code. This is usually omitted
    and should be assumed to be 200 unless specified
- `head`: The map of header-field in the response. Each entry 
    may be a string, or a sequence of strings
- `body`: The message body as a a string; it may be omitted 
    if not relevant to the functionality being tested
