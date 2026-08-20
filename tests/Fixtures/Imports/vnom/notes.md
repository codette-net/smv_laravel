# VNOM import notes

Source:
https://www.vnom.nl/feeds/jobs.xml

Record:
`job`

Legacy WP All Import filter:

```xpath
/job[
    function[1][contains(.,"Sales")]
    or function[1][contains(.,"Marketing")]
]
