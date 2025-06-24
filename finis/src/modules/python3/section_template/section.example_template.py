import sys
import json
import os

def load_template(language='it'):
    """Load HTML template based on language"""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    template_path = os.path.join(script_dir, f'section.{language}.html')
    
    # Fallback to Italian if specific language file doesn't exist
    if not os.path.exists(template_path):
        template_path = os.path.join(script_dir, 'section.it.html')
    
    try:
        with open(template_path, 'r', encoding='utf-8') as file:
            return file.read()
    except FileNotFoundError:
        return "<h1>Template not found</h1>"

def main():
    try:
        json_data = sys.argv[1]
        data = json.loads(json_data)
        
        # Get language from data, default to 'en'
        language = data.get('lang', 'en')
        
        # Load and display template
        template_content = load_template(language)
        print(template_content)
        
    except json.JSONDecodeError:
        print("<p>Error: Invalid JSON data</p>")
        sys.exit(1)
    except Exception as e:
        print(f"<p>Error: {str(e)}</p>")
        sys.exit(1)

if __name__ == "__main__":
    main()
