# This script processes JSON data passed from PHP and prints a message
import sys
import json

def main():
    # Print greeting message
    print("<br />Hello world!")
    # Attempt to process JSON data
    try:
        json_data = sys.argv[1]
        # Parse the JSON data
        data = json.loads(json_data)
        print("<br />Site name:", data['sitename'])
        print("<br />Username:", data['user'])
        
    except json.JSONDecodeError:
        # Print error message if JSON is invalid
        print("Error: Invalid JSON")
        sys.exit(1)
    

# Execute main function
if __name__ == "__main__":
    main()
